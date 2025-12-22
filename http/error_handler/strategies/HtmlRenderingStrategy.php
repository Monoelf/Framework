<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\error_handler\strategies;

use Monoelf\Framework\common\exceptions\BaseException;
use Monoelf\Framework\common\RenderingStrategyInterface;
use Monoelf\Framework\config_storage\ConfigurationStorage;
use Monoelf\Framework\http\exceptions\HttpException;
use Monoelf\Framework\logger\DebugTagStorage;
use Monoelf\Framework\view\ViewInterface;
use Throwable;

final readonly class HtmlRenderingStrategy implements RenderingStrategyInterface
{
    public function __construct(
        private DebugTagStorage $debugTagStorage,
        private ConfigurationStorage $configurationStorage,
        private ViewInterface $view,
    ) {}

    public function execute(Throwable $throwable): string
    {
        $message = $throwable instanceof BaseException === true
            ? json_encode($throwable->getRawMessage(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : $throwable->getMessage();
        $trace = str_replace(["\n", ": "], ["\n\n", ":\n"], $throwable->getTraceAsString());
        $type = $throwable::class;
        $statusCode = $throwable instanceof HttpException === true ? $throwable->getStatusCode() : 500;

        return $this->view->render('error', [
            'message' => $message,
            'trace' => $trace,
            'type' => $type,
            'statusCode' => $statusCode,
            'xDebugTag' => $this->debugTagStorage->getTag(),
            'showTrace' => (int)$this->configurationStorage->getOrDefault('DEBUG', 0) === 1
        ]);
    }
}
