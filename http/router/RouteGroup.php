<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\router;

final class RouteGroup implements MiddlewareAssignable
{
    private array $groups = [];
    private array $routes = [];
    private array $middlewares = [];
    public function __construct(private readonly string $name) {}

    public function addMiddleware(callable|string $middleware): MiddlewareAssignable
    {
        foreach ($this->groups as $group) {
            $group->addMiddleware($middleware);
        }

        foreach ($this->routes as $route) {
            $route->addMiddleware($middleware);
        }

        $this->middlewares[] = $middleware;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function addGroup(RouteGroup $group): void
    {
        $this->groups[] = $group;

        foreach ($this->middlewares as $middleware) {
            $group->addMiddleware($middleware);
        }
    }

    public function addRoute(Route $route): void
    {
        $this->routes[] = $route;

        foreach ($this->middlewares as $middleware) {
            $route->addMiddleware($middleware);
        }
    }
}
