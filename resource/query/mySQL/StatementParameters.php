<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource\query\mySQL;

final readonly class StatementParameters
{
    public function __construct(
        public string $sql,
        public array $bindings
    ) {}
}
