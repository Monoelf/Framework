<?php

declare(strict_types=1);

namespace Monoelf\Framework\validator\rule_validators;

use DateMalformedStringException;
use DateTime;
use Monoelf\Framework\validator\RuleValidatorInterface;
use Monoelf\Framework\validator\ValidationException;

final class DateValidator implements RuleValidatorInterface
{
    public function validate(mixed $value, array $options = []): void
    {
        if (isset($options['format']) === true) {
            if (
                DateTime::createFromFormat($options['format'], $value) === false
                || DateTime::getLastErrors() !== false
            ) {
                throw new ValidationException('Значение должно быть датой в формате ' . $options['format']);
            }

            return;
        }

        try {
            new DateTime($value);
        } catch (DateMalformedStringException) {
            throw new ValidationException('Значение должно быть датой');
        }
    }
}
