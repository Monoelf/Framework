<?php

declare(strict_types=1);

namespace Monoelf\Framework\console;

final class ConsoleEvent
{
    public const CONSOLE_INPUT_BEFORE_PARSE = self::class . '.CONSOLE_INPUT_BEFORE_PARSE';
    public const CONSOLE_INPUT_AFTER_PARSE = self::class . '.CONSOLE_INPUT_AFTER_PARSE';
    public const CONSOLE_INPUT_AFTER_VALIDATE = self::class . '.CONSOLE_INPUT_AFTER_VALIDATE';
}
