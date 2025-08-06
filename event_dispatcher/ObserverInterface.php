<?php

declare(strict_types=1);

namespace Monoelf\Framework\event_dispatcher;

interface ObserverInterface
{
    public function handle(string $eventName, Message $message): void;
}
