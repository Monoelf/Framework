<?php

declare(strict_types=1);

namespace Monoelf\Framework\console\plugin;

Monoelf\Framework\console\ConsoleInputInterface;
Monoelf\Framework\event_dispatcher\EventDispatcherInterface;

interface ConsoleInputPluginInterface
{
    public function init(ConsoleInputInterface $input, EventDispatcherInterface $dispatcher): void;
}
