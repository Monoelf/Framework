<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\error_handler\strategies;

use Monoelf\Framework\common\RenderingStrategyInterface;
use Monoelf\Framework\config_storage\ConfigurationStorage;
use Monoelf\Framework\logger\DebugTagStorage;
use Monoelf\Framework\view\ViewInterface;
use Throwable;

final class HtmlRenderingStrategy implements RenderingStrategyInterface
{
    public function __construct(
        private readonly DebugTagStorage $debugTagStorage,
        private readonly ConfigurationStorage $configurationStorage,
        private readonly ViewInterface $view,
    ) {}

    public function execute(Throwable $throwable): string
    {
        return $this->view->render('error', [
            'exception' => $throwable,
            'xDebugTag' => $this->debugTagStorage->getTag(),
            'showTrace' => (int)$this->configurationStorage->getOrDefault('DEBUG', 0) === 1
        ]);
    }
}
