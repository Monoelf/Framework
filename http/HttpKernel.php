<?php

declare(strict_types=1);

namespace Monoelf\Framework\http;

use Monoelf\Framework\common\ErrorHandlerInterface;
use Monoelf\Framework\common\ModuleInterface;
use Monoelf\Framework\config_storage\ConfigurationStorage;
use Monoelf\Framework\container\ContainerInterface;
use Monoelf\Framework\http\dto\BaseControllerResponse;
use Monoelf\Framework\http\exceptions\HttpException;
use Monoelf\Framework\http\exceptions\HttpNotAcceptableException;
use Monoelf\Framework\http\router\HTTPRouterInterface;
use Monoelf\Framework\logger\LoggerInterface;
use \Monoelf\Framework\http\HTTPKernelInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Ядро обработки обработки HTTP-запросов
 */
final class HttpKernel implements HttpKernelInterface
{
    public function __construct(
        private readonly HTTPRouterInterface $router,
        private readonly LoggerInterface $logger,
        private readonly ErrorHandlerInterface $errorHandler,
        private readonly ContainerInterface $container,
        private readonly ConfigurationStorage $configurationStorage,
        array $modules = [],
    ) {
        $this->initModules($modules);
    }

    public function handle(ServerRequestInterface $request): ServerResponseInterface
    {
        try {
            $result = $this->router->dispatch($request);

            $message = null;
            $statusCode = StatusCodeEnum::STATUS_OK->value;
            $responseContentType = 'text/html; charset=utf-8';

            if ($result instanceof BaseControllerResponse === true) {
                $statusCode = $result->statusCode;
                $result = $result->responseBody;
            }

            if (is_array($result) === true) {
                $responseContentType = 'application/json';
                $message = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $isContentTypeAccepted = $this->isContentTypeAccepted(
                $responseContentType,
                $request->getHeader('Accept')
            );

            if ($isContentTypeAccepted === false) {
                throw new HttpNotAcceptableException();
            }

            $response = $this->container->get(ServerResponseInterface::class)
                ->withStatus($statusCode)
                ->withHeader('Content-Type', $responseContentType);

            $response->getBody()->write($message ?? (string)$result);
        } catch (HttpException $e) {
            $this->logger->error($e);

            $body = $this->errorHandler->handle($e);

            $response = $this->container->get(ServerResponseInterface::class)
                ->withStatus(
                    $e->getStatusCode(),
                    $e->getMessage()
                );

            if ($response->hasHeader('Content-Type') === false) {
                $response = $response->withHeader('Content-Type', 'text/html; charset=utf-8');
            }

            $response->getBody()->write($body);
        } catch (\Throwable $e) {
            $this->logger->error($e);

            $body = $this->errorHandler->handle($e);

            $response = $this->container->get(ServerResponseInterface::class)
                ->withStatus(
                    StatusCodeEnum::STATUS_INTERNAL_SERVER_ERROR->value,
                    $e->getMessage()
                );

            if ($response->hasHeader('Content-Type') === false) {
                $response = $response->withHeader('Content-Type', 'text/html; charset=utf-8');
            }

            $response->getBody()->write($body);
        }

        return $response;
    }

    private function isContentTypeAccepted(?string $contentType, ?array $acceptTypes): bool
    {
        if (empty($acceptTypes) === true) {
            return true;
        }

        if ($contentType === null) {
            return false;
        }

        $contentTypeBase = trim(explode(';', $contentType)[0]);

        foreach ($acceptTypes as $acceptType) {
            $acceptTypeBase = trim(explode(';', $acceptType)[0]);
            $regex = '/^' . str_replace('\*', '.*', preg_quote($acceptTypeBase, '/')) . '$/';

            if (preg_match($regex, $contentTypeBase) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Инициализация модулей
     *
     * @param array $modules
     * @return void
     */
    private function initModules(array $modules): void
    {
        foreach ($modules as $module) {
            if (is_subclass_of($module, ModuleInterface::class) === false) {
                throw new \InvalidArgumentException("Модуль {$module} не реализует интерфейс " . ModuleInterface::class);
            }

            $this->container->call($module, 'init');
        }
    }
}
