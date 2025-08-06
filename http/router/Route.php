<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\router;

final class Route implements MiddlewareAssignable
{
    public function __construct(
        public string $method,
        public string $path,
        public array  $handler,
        public array  $middlewares = [],
        public array  $params = [],
    ) {}

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
}
