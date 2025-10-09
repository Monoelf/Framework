<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\router;

use Monoelf\Framework\container\ContainerInterface;
use Monoelf\Framework\http\exceptions\HttpBadRequestException;
use Monoelf\Framework\http\exceptions\HttpNotFoundException;
use InvalidArgumentException;
use Monoelf\Framework\http\ServerResponseInterface;
use Monoelf\Framework\validator\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class Router implements HTTPRouterInterface, MiddlewareAssignable
{
    private array $routes = [];
    private array $middlewares = [];

    /**
     * @var RouteGroup[]
     */
    private array $groupStack = [];

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly Validator $validator,
    )
    {
    }

    public function addMiddleware(callable|string $middleware): MiddlewareAssignable
    {
        if (is_callable($middleware) === true) {
            $this->middlewares[] = $middleware(...);

            return $this;
        }

        if (class_exists($middleware) === false) {
            throw new \InvalidArgumentException("Не найден мидлвеер '{$middleware}'");
        }

        if (is_subclass_of($middleware, MiddlewareInterface::class) === false) {
            throw new \InvalidArgumentException("Мидлвеер должен реализовывать MiddlewareInterface");
        }

        $this->middlewares[] = $middleware;

        return $this;
    }

    public function get(string $route, callable|string|array $handler): Route
    {
        return $this->add('GET', $route, $handler);
    }

    public function post(string $route, callable|string|array $handler): Route
    {
        return $this->add('POST', $route, $handler);
    }

    public function put(string $route, callable|string|array $handler): Route
    {
        return $this->add('PUT', $route, $handler);
    }

    public function patch(string $route, callable|string|array $handler): Route
    {
        return $this->add('PATCH', $route, $handler);
    }

    public function delete(string $route, callable|string|array $handler): Route
    {
        return $this->add('DELETE', $route, $handler);
    }

    public function group(string $name, callable $set): RouteGroup
    {
        $group = new RouteGroup($name);

        if ($this->groupStack !== []) {
            $this->groupStack[array_key_last($this->groupStack)]->addGroup($group);
        }

        $this->groupStack[] = $group;

        $set($this);

        return array_pop($this->groupStack);
    }

    public function add(string $method, string $path, string|callable|array $handler): Route
    {
        $method = strtoupper($method);
        $fullPath = $this->buildFullPath($path);
        $regex = $this->buildRegexPath($fullPath);

        $route = new Route(
            $method,
            $fullPath,
            $regex,
            $this->resolveHandler($handler),
            $this->middlewares,
            $this->prepareParams($path)
        );

        $this->routes[$method][$fullPath] = $route;

        if ($this->groupStack !== []) {
            $this->groupStack[array_key_last($this->groupStack)]->addRoute($route);
        }

        return $route;
    }

    public function has(string $method, string $path): bool
    {
        $method = strtoupper($method);

        if (isset($this->routes[$method]) === false) {
            return false;
        }

        foreach ($this->routes[$method] as $possibleRoute) {
            if (preg_match($possibleRoute->regex, $path) === 1) {
                return true;
            }
        }

        return false;
    }

    public function dispatch(ServerRequestInterface $request): mixed
    {
        $method = strtoupper($request->getMethod());
        $path = $request->getUri()->getPath();

        if ($this->has($method, $path) === false) {
            throw new HttpNotFoundException("Маршрут не найден: {$method} {$path}");
        }

        foreach ($this->routes[$method] as $possibleRoute) {
            if (preg_match($possibleRoute->regex, $path, $matches) === 1) {
                $route = $possibleRoute;
                $pathParams = array_filter(
                    $matches,
                    fn($key) => is_int($key) === false,
                    ARRAY_FILTER_USE_KEY
                );
                $pathParams = array_map('urldecode', $pathParams);

                break;
            }
        }

        $params = $this->mapParams(array_merge($request->getQueryParams(), $pathParams), $route->params);

        $middlewareChain = array_reduce(
            array_reverse($route->middlewares),
            function (callable $next, string|callable $middleware): callable {
                return function (ServerRequestInterface $request, ServerResponseInterface $response) use ($middleware, $next) {
                    $args = ['request' => $request, 'response' => $response, 'next' => $next];
                    $this->container->call($middleware, '__invoke', $args);
                };
            },
            function (
                ServerRequestInterface $request,
                ServerResponseInterface $response
            ) {
                $this->container->registerSingleton(ServerRequestInterface::class, fn () => $request);
                $this->container->registerSingleton(ServerResponseInterface::class, fn () => $response);
            }
        );

        $this->container->call($middlewareChain, '__invoke', ['request' => $request]);

        return $this->container->call($route->handler[0], $route->handler[1], $params);
    }


    /**
     * Формирование массива параметров вызовов обработчика маршрута
     *
     * @param string|callable $handler обработчик - коллбек функция
     * или неймспейс класса в формате 'Неймспейс::метод'
     * @return array
     * Пример для callable:
     * [Closure, '__invoke']
     * Пример для string:
     * ['Неймспейс', 'метод'];
     */
    private function resolveHandler(callable|string|array $handler): array
    {
        if (is_callable($handler) === true) {
            return [$handler(...), '__invoke'];
        }

        if (is_array($handler) === true) {
            return $handler;
        }

        if (str_contains($handler, '::') === true) {
            $parts = explode('::', $handler, 2);

            if (count($parts) !== 2) {
                throw new InvalidArgumentException("Неймспейс класса должен быть в формате 'Неймспейс::метод'");
            }

            return $parts;
        }

        throw new InvalidArgumentException("Обработчик должен быть коллбеком или неймспейсом класса в формате 'Неймспейс::метод'");
    }

    /**
     * Получение параметров запроса из маршрута
     *
     * @param string $route маршрут
     * Пример:
     * "/path?{firstNumber}{?secondNumber=900}"
     * @return array
     * Пример:
     * [
     *     [
     *         'name' => 'firstNumber',
     *         'required' => true,
     *         'default' => null,
     *     ],
     *     [
     *         'name' => 'secondNumber',
     *         'required' => false,
     *         'default' => 900,
     *     ],
     * ]
     */
    private function prepareParams(string $route): array
    {
        preg_match_all('/\{(\??):(\w+)(?:\|(\w+))?(?:=(\w+))?}/', $route, $matches, PREG_SET_ORDER);

        $params = [];

        foreach ($matches as $match) {
            $params[] = [
                'name' => $match[2],
                'type' => $match[3] ?? 'string', // по умолчанию string
                'required' => $match[1] !== '?',
                'default' => $match[4] ?? null,
            ];
        }

        return $params;
    }


    /**
     * Получение значений параметров запроса определенных для маршрута
     *
     * Пример:
     * "/path?firstNumber=700"
     * "/path?{firstNumber}{?secondNumber=900}"
     *
     * @param array $queryParams параметры из запроса
     * @param array $params подготовленные параметры определенных для запроса
     * @return array
     * Пример:
     * ['firstNumber' => 700, 'secondNumber' => 900]
     * @throws HttpBadRequestException
     */
    private function mapParams(array $queryParams, array $params): array
    {
        $result = [];

        foreach ($params as $param) {
            $name = $param['name'];
            $value = $queryParams[$name] ?? $param['default'];

            if ($value === null && $param['required'] === true) {
                throw new HttpBadRequestException("Отсутствует обязательный параметр: {$name}");
            }

            if ($value !== null) {
                $value = $this->validateParams($value, $param['type'], $name);
            }

            $result[$name] = $value;
        }

        return $result;
    }

    /**
     * @param mixed $value
     * @param string $type
     * @param string $name
     * @return mixed
     * @throws HttpBadRequestException
     */
    private function validateParams(mixed $value, string $type, string $name): mixed
    {
        try {
            $this->validator->validate($value, $type);
            return $value;
        } catch (Throwable $e) {
            throw new HttpBadRequestException(
                "Ошибка валидации параметра '{$name}': " . $e->getMessage()
            );
        }
    }

    /**
     * Построение полного пути на основе групп
     *
     * @param string $route основной путь
     * @return string полный путь
     */
    private function buildFullPath(string $route): string
    {
        $pathOnly = explode('?', $route, 2)[0];

        if ($this->groupStack === []) {
            return $pathOnly;
        }

        $fullPath = '';

        foreach ($this->groupStack as $group) {
            $fullPath .= '/' . $group->getName();
        }

        return $fullPath . $pathOnly;
    }

    /**
     * Построение регулярки для пути с path параметрами на основе шаблона
     *
     * @param string $routeTemplate шаблон, пример: '/path/delete/{name}?{id}'
     * @return string регулярка, пример '#^/path/delete/(?P<name>[^/]+)$#'
     */
    private function buildRegexPath(string $routeTemplate): string
    {
        $regex = preg_replace_callback(
            '/\{(\??):(\w+)(?:\|(\w+))?(?:=(\w+))?}/',
            function ($match) {
                $name = $match[2];

                return "(?P<{$name}>[^/]+)";
            },
            explode('?', $routeTemplate, 2)[0]
        );

        return '#^' . $regex . '$#';
    }


    /**
     * @param string $name
     * @param string $controller
     * @param array $config
     * @return void
     */
    public function addResource(string $name, string $controller, array $config = []): void
    {
        (new Resource($name, $controller, $config))->build($this);
    }
}
