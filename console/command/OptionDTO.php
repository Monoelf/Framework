<?php

declare(strict_types=1);

namespace Monoelf\Framework\console\command;

final class OptionDTO
{
    public function __construct(
        public string $name,
        public bool $hasValue = false,
        public ?string $description = null
    ) {}
}
