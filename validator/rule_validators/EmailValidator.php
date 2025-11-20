<?php

declare(strict_types=1);

namespace Monoelf\Framework\validator\rule_validators;

use Monoelf\Framework\validator\RuleValidatorInterface;
use Monoelf\Framework\validator\ValidationException;

final class EmailValidator implements RuleValidatorInterface
{
    public function validate(mixed $value, array $options = []): void
    {
        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new ValidationException('Значение должно быть корректным email адресом');
        }
    }
}
