<?php

declare(strict_types=1);


namespace Monoelf\Framework\logger;

interface DebugTagStorageInterface
{
    public function getTag(): string;
    public function setTag(string $tag): void;
}
