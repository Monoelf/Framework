<?php

declare(strict_types=1);

namespace Monoelf\Framework\validator\rule_validators;

use Monoelf\Framework\validator\RuleValidatorInterface;
use Monoelf\Framework\validator\ValidationException;

final class BooleanValidator implements RuleValidatorInterface
{
    public function validate(mixed $value, array $options = []): void
    {
        if (filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null) {
            throw new ValidationException('Значение должно быть логического типа');
        }
    }
}
