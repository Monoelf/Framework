<?php

declare(strict_types=1);

namespace Monoelf\Framework\console\plugin;

use Monoelf\Framework\console\ConsoleInputInterface;
use Monoelf\Framework\event_dispatcher\EventDispatcherInterface;

interface ConsoleInputPluginInterface
{
    public function init(ConsoleInputInterface $input, EventDispatcherInterface $dispatcher): void;
}
