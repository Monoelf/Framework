<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\error_handler\strategies;

use Monoelf\Framework\common\RenderingStrategyInterface;
use Monoelf\Framework\container\ContainerInterface;
use Monoelf\Framework\http\ServerResponseInterface;
use Monoelf\Framework\logger\DebugTagStorage;
use Throwable;

final class JsonRenderingStrategy implements RenderingStrategyInterface
{
    public function __construct(
        private readonly DebugTagStorage $debugTagStorage,
        private readonly ContainerInterface $container,
    ) {}

    public function execute(Throwable $throwable): string
    {
        $response = $this->container->get(ServerResponseInterface::class);
        $response = $response->withHeader('Content-Type', 'application/json');
        $this->container->registerSingleton(ServerResponseInterface::class, fn () => $response);

        return json_encode([
            'message' => $throwable->getMessage(),
            'x-debug-tag' =>  $this->debugTagStorage->getTag(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
