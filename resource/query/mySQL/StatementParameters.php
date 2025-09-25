<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource\query\mySQL;

final readonly class StatementParameters
{
    public function __construct(
        public string $sql,
        public array $bindings
    ) {}

    public function getSql(): string
    {
        return $this->sql;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }
}
