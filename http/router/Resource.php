<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\router;

class Resource
{
    /**
     * @param string $name
     * @param string $controller
     * @param array $config
     */
    public function __construct(
        private readonly string $name,
        private readonly string $controller,
        private array           $config = []
    )
    {
    }

    /**
     * @param HTTPRouterInterface $router
     * @return void
     */
    public function build(HTTPRouterInterface $router): void
    {
        foreach ($this->getConfiguration($this->name) as $params) {
            $path = $params['path'];

            $fullPath = rtrim($this->name, '/')
                . ($path !== '' ? '/' . ltrim($path, '/') : '');

            if (str_starts_with($path, $this->name)) {
                $fullPath = $path;
            }

            $fullPath = '/' . ltrim($fullPath, '/');

            $route = $router->add(
                $params['method'],
                $fullPath,
                $this->controller . '::' . $params['action']
            );

            if (empty($params['middleware']) === false) {
                foreach ($params['middleware'] as $middleware) {
                    $route->addMiddleware($middleware);
                }
            }

        }
    }

    /**
     * @param string $path
     * @return array[]
     */
    private function getConfiguration(string $path): array
    {
        $config = [
            'index' => [
                'method' => 'GET',
                'path' => $path,
                'action' => 'actionList',
                'middleware' => [],
            ],
            'view' => [
                'method' => 'GET',
                'path' => "{$path}/{:id|integer}",
                'action' => 'actionView',
                'middleware' => [],
            ],
            'create' => [
                'method' => 'POST',
                'path' => $path,
                'action' => 'actionCreate',
                'middleware' => [],
            ],
            'put' => [
                'method' => 'PUT',
                'path' => "{$path}/{:id|integer}",
                'action' => 'actionUpdate',
                'middleware' => [],
            ],
            'patch' => [
                'method' => 'PATCH',
                'path' => "{$path}/{:id|integer}",
                'action' => 'actionPatch',
                'middleware' => [],
            ],
            'delete' => [
                'method' => 'DELETE',
                'path' => "{$path}/{:id|integer}",
                'action' => 'actionDelete',
                'middleware' => [],
            ],
        ];

        foreach ($this->config as $method => $overrides) {
            if (isset($config[$method]) === false) {
                continue;
            }

            if (isset($overrides['middleware']) === true) {
                $config[$method]['middleware'] = array_merge(
                    $config[$method]['middleware'],
                    (array)$overrides['middleware']
                );
            }
        }

        return $config;
    }
}