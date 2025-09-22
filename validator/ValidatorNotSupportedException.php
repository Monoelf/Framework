<?php

declare(strict_types=1);

namespace Monoelf\Framework\validator;

use RuntimeException;

final class ValidatorNotSupportedException extends RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct("Валидатор для правила '{$message}' не задан");
    }
}
