<?php

declare(strict_types=1);

namespace Monoelf\Framework\console;

use Monoelf\Framework\common\ErrorHandlerInterface;
use Monoelf\Framework\view\ViewInterface;
use Monoelf\Framework\view\ViewNotFoundException;
use Throwable;

final class ConsoleErrorHandler implements ErrorHandlerInterface
{
    public function __construct(
        private readonly AnsiDecorator $decorator,
        private readonly ViewInterface $renderer,
    ) {}

    public function handle(Throwable $throwable): string
    {
        $params = [
            'exception' => $throwable,
            'decorator' => $this->decorator,
        ];

        try {
            return $this->renderer->render('error', $params);
        } catch (ViewNotFoundException) {
            return $this->renderer->render('@framework/console/error', $params);
        }
    }
}
