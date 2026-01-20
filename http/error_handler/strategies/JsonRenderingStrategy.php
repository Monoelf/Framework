<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\error_handler\strategies;

use Monoelf\Framework\common\exceptions\BaseException;
use Monoelf\Framework\common\RenderingStrategyInterface;
use Monoelf\Framework\container\ContainerInterface;
use Monoelf\Framework\http\ServerResponseInterface;
use Monoelf\Framework\logger\DebugTagStorage;
use Throwable;

final readonly class JsonRenderingStrategy implements RenderingStrategyInterface
{
    public function __construct(
        private DebugTagStorage $debugTagStorage,
        private ContainerInterface $container,
    ) {}

    public function execute(Throwable $throwable): string
    {
        $response = $this->container->get(ServerResponseInterface::class);
        $response = $response->withHeader('Content-Type', 'application/json');
        $this->container->registerSingleton(ServerResponseInterface::class, fn(): ServerResponseInterface => $response);

        $message = $throwable instanceof BaseException === true ? $throwable->getRawMessage() : $throwable->getMessage();

        return json_encode([
            'message' => $message,
            'x-debug-tag' =>  $this->debugTagStorage->getTag(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
