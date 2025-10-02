<?php

declare(strict_types=1);

namespace Monoelf\Framework\validator\rule_validators;

use Monoelf\Framework\validator\RuleValidatorInterface;
use Monoelf\Framework\validator\ValidationException;

final class RequiredValidator implements RuleValidatorInterface
{
    public function validate(mixed $value, array $options = []): void
    {
        if (is_null($value) === true || $value === '' || $value === []) {
            throw new ValidationException('Значение обязательно для заполнения');
        }
    }
}
