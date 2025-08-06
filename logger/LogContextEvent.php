<?php

declare(strict_types=1);

namespace Monoelf\Framework\logger;

final class LogContextEvent
{
    const ATTACH_CONTEXT = self::class . '.ATTACH_CONTEXT';
    const DETACH_CONTEXT = self::class . '.DETACH_CONTEXT';
    const FLUSH_CONTEXT = self::class . '.FLUSH_CONTEXT';
    const ATTACH_EXTRAS = self::class . '.ATTACH_EXTRAS';
    const FLUSH_EXTRAS = self::class . '.FLUSH_EXTRAS';
    const ATTACH_CATEGORY = self::class . '.ATTACH_CATEGORY';
    const FLUSH_CATEGORY = self::class . '.FLUSH_CATEGORY';
}
