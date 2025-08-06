<?php

declare(strict_types=1);

use Monoelf\Framework\console\AnsiDecorator;
use Monoelf\Framework\console\ColorsEnum;

/**
 * @var Throwable $exception
 * @var AnsiDecorator $decorator
 */

echo $decorator->decorate(
        sprintf('[%1$s] Uncaught %1$s: %2$s', get_class($exception), $exception->getMessage()),
        [ColorsEnum::BG_RED->value, ColorsEnum::FG_WHITE->value]
    ) . PHP_EOL . PHP_EOL;

foreach (explode("\n", $exception->getTraceAsString()) as $line) {
    echo $decorator->decorate($line, [ColorsEnum::FG_WHITE->value]) . PHP_EOL . PHP_EOL;
}
