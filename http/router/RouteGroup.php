<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\router;

final class RouteGroup implements MiddlewareAssignable
{
    private array $middlewares = [];

    public function __construct(private readonly string $name) {}

    public function addMiddleware(callable|string $middleware): MiddlewareAssignable
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
