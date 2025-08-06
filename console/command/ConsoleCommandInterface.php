<?php

declare(strict_types=1);

namespace Monoelf\Framework\console\command;

Monoelf\Framework\console\ConsoleInputInterface;
Monoelf\Framework\console\ConsoleOutputInterface;

interface ConsoleCommandInterface
{
    function execute(ConsoleInputInterface $input, ConsoleOutputInterface $output): void;

    static function getSignature(): string;

    static function getDescription(): string;
}
