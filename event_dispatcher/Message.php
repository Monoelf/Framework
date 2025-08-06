<?php

declare(strict_types=1);

namespace Monoelf\Framework\event_dispatcher;

final class Message
{
    public function __construct(public readonly mixed $message = null) {}
}
