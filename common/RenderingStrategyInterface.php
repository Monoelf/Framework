<?php

declare(strict_types=1);

namespace Monoelf\Framework\common;

use Throwable;

interface RenderingStrategyInterface
{
    public function execute(Throwable $throwable): string;
}
