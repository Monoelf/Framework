<?php

declare(strict_types=1);

namespace Monoelf\Framework\http;

use Monoelf\Framework\common\ErrorHandlerInterface;
use Monoelf\Framework\config_storage\ConfigurationStorage;
use Monoelf\Framework\logger\DebugTagStorageInterface;
use Monoelf\Framework\view\ViewInterface;
use Monoelf\Framework\view\ViewNotFoundException;

final class HttpErrorHandler implements ErrorHandlerInterface
{
    public function __construct(
        private readonly ViewInterface $view,
        private readonly DebugTagStorageInterface $debugTagStorage,
        private readonly ConfigurationStorage $configurationStorage
    ) {}

    public function handle(\Throwable $throwable): string
    {
        $params = [
            'exception' => $throwable,
            'xDebugTag' => $this->debugTagStorage->getTag(),
            'showTrace' => (int)$this->configurationStorage->getOrDefault('DEBUG', 0) === 1
        ];

        try {
            return $this->view->render('error', $params);
        } catch (ViewNotFoundException) {
            return $this->view->render('@framework/http/error', $params);
        }
    }
}
