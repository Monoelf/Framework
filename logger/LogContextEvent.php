<?php

declare(strict_types=1);

namespace Monoelf\Framework\logger;

final class LogContextEvent
{
    public const ATTACH_CONTEXT = self::class . '.ATTACH_CONTEXT';
    public const DETACH_CONTEXT = self::class . '.DETACH_CONTEXT';
    public const FLUSH_CONTEXT = self::class . '.FLUSH_CONTEXT';
    public const ATTACH_EXTRAS = self::class . '.ATTACH_EXTRAS';
    public const FLUSH_EXTRAS = self::class . '.FLUSH_EXTRAS';
    public const ATTACH_CATEGORY = self::class . '.ATTACH_CATEGORY';
    public const FLUSH_CATEGORY = self::class . '.FLUSH_CATEGORY';
}
