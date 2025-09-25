<?php

declare(strict_types=1);

namespace Monoelf\Framework\validator\rule_validators;

use Monoelf\Framework\validator\RuleValidatorInterface;

final class SafeValidator implements RuleValidatorInterface
{
    /**
     * Всегда успешная валидация
     * @param mixed $value
     * @param array $options
     * @return void
     */
    public function validate(mixed $value, array $options = []): void {}
}
