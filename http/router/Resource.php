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
        private array $config = []
    ) {}

    /**
     * @param HTTPRouterInterface $router
     * @return void
     */
    public function build(HTTPRouterInterface $router): void
    {
        foreach ($this->getConfiguration($this->name) as $params) {
            $route = $router->add(
                $params['method'],
                $this->name . ($params['path'] !== '' ? '/' . ltrim($params['path'], '/') : ''),
                $this->controller . '::' . $params['action']
            );

            if (empty($params['middleware']) === false) {
                $route->addMiddleware($params['middleware']);
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
                'path' =>'',
                'action' => 'actionList',
                'middleware' => [],
            ],
            'view' => [
                'method' => 'GET',
                'path' => "{:id|integer}",
                'action' => 'actionView',
                'middleware' => [],
            ],
            'create' => [
                'method' => 'POST',
                'path' => '',
                'action' => 'actionCreate',
                'middleware' => [],
            ],
            'put' => [
                'method' => 'PUT',
                'path' => "{:id|integer}",
                'action' => 'actionUpdate',
                'middleware' => [],
            ],
            'patch' => [
                'method' => 'PATCH',
                'path' => "",
                'action' => 'actionPatch',
                'middleware' => [],
            ],
            'delete' => [
                'method' => 'DELETE',
                'path' => "",
                'action' => 'actionDelete',
                'middleware' => [],
            ],
        ];

        foreach ($this->config as $newMethod => $elements) {
            if (isset($config[$newMethod]) === false) {
                $config[$newMethod] = $elements;

                continue;
            }

            $config[$newMethod] = array_merge($config[$newMethod], $elements);
        }

        return $config;
    }
}