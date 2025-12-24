<?php

declare(strict_types=1);

namespace Monoelf\Framework\common\exceptions;

use Exception;

class BaseException extends Exception
{
    public function __construct(private readonly mixed $mixedMessage)
    {
        if (is_string($this->mixedMessage) === true) {
            parent::__construct($this->mixedMessage);
        }
    }

    public function getRawMessage(): mixed
    {
        return $this->mixedMessage;
    }
}
