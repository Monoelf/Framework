<?php

declare(strict_types=1);

namespace Monoelf\Framework\console\command;

final class ArgumentDTO
{
    public function __construct(
        public string $name,
        public bool $required,
        public mixed $default = null,
        public ?string $description = null,
    ) {}
}
