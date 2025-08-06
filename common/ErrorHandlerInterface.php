<?php

declare(strict_types=1);

namespace Monoelf\Framework\common;

use Throwable;

interface ErrorHandlerInterface
{
    /**
     * @param Throwable $throwable объект ошибки
     * @return string
     */
    public function handle(Throwable $throwable): string;
}
