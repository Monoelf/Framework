<?php

declare(strict_types=1);

namespace Monoelf\Framework\validator\rule_validators;

use InvalidArgumentException;
use Monoelf\Framework\validator\RuleValidatorInterface;
use Monoelf\Framework\validator\ValidationException;

final class StringValidator implements RuleValidatorInterface
{
    public function validate(mixed $value, array $options = []): void
    {
        if (empty($value) === true) {
            return;
        }

        if (is_string($value) === false) {
            throw new ValidationException('Значение должно быть строкой');
        }

        $len = mb_strlen($value);
        $min = $options['min'] ?? null;
        $max = $options['max'] ?? null;
        $pattern = $options['pattern'] ?? null;

        if ($min !== null && $len < $min) {
            throw new ValidationException('Значение должно быть не короче минимальной длины: ' . $min);
        }

        if ($max !== null && $len > $max) {
            throw new ValidationException('Значение должно быть не длиннее максимальной длины: ' . $max);
        }

        if ($pattern !== null) {
            $pregResult = preg_match($pattern, $value);

            if ($pregResult === false) {
                throw new InvalidArgumentException('Некорректно задано правило паттерна');
            }

            if ($pregResult === 0) {
                throw new ValidationException('Значение не соответсвует паттерну');
            }
        }
    }
}
