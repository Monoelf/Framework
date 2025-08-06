<?php

declare(strict_types=1);

namespace Monoelf\Framework\logger;

enum LogLevel: string
{
    case CRITICAL = 'critical';
    case ERROR = 'error';
    case WARNING = 'warning';
    case INFO = 'info';
    case DEBUG = 'debug';

    public static function getIndex(string $name): int
    {
        return match ($name) {
            self::CRITICAL->value => 0,
            self::ERROR->value => 1,
            self::WARNING->value => 2,
            self::INFO->value => 3,
            self::DEBUG->value => 4,
            default => -1,
        };
    }
}
