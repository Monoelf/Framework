<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\error_handler;

use Monoelf\Framework\common\ErrorHandlerInterface;
use Monoelf\Framework\config_storage\ConfigurationStorage;
use Monoelf\Framework\container\ContainerInterface;
use Monoelf\Framework\http\error_handler\strategies\HtmlRenderingStrategy;
use Monoelf\Framework\http\error_handler\strategies\JsonRenderingStrategy;
use Monoelf\Framework\http\error_handler\strategies\StrategyNotFoundException;
use Monoelf\Framework\logger\DebugTagStorageInterface;
use Monoelf\Framework\view\ViewInterface;
use Monoelf\Framework\view\ViewNotFoundException;

final class HttpErrorHandler implements ErrorHandlerInterface
{
    private array $defaultRenderingStrategies = [
        'html' => HtmlRenderingStrategy::class,
        'json' => JsonRenderingStrategy::class,
    ];

    private readonly array $renderingStrategies;

    public function __construct(
        private readonly ViewInterface $view,
        private readonly DebugTagStorageInterface $debugTagStorage,
        private readonly ConfigurationStorage $configurationStorage,
        private readonly ContainerInterface $container,
        array $renderingStrategies = [],
        private string $mode = 'html',
    ) {
        $this->renderingStrategies = array_merge_recursive($renderingStrategies, $this->defaultRenderingStrategies);
    }

    public function handle(\Throwable $throwable): string
    {
        try {
            if (isset($this->renderingStrategies[$this->mode]) === false) {
                throw new StrategyNotFoundException("Стратегия для режима {$this->mode} не найдена");
            }

            return $this->container->call(
                $this->renderingStrategies[$this->mode],
                'execute',
                ['throwable' => $throwable]
            );
        } catch (ViewNotFoundException | StrategyNotFoundException) {
            return $this->view->render('@framework/http/error', [
                'exception' => $throwable,
                'xDebugTag' => $this->debugTagStorage->getTag(),
                'showTrace' => (int)$this->configurationStorage->getOrDefault('DEBUG', 0) === 1
            ]);
        }
    }

    public function defineMode(string $mode): void
    {
        $this->mode = $mode;
    }
}
