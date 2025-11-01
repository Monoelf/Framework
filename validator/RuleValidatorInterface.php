<?php

declare(strict_types=1);

namespace Monoelf\Framework\validator;

interface RuleValidatorInterface
{
    /**
     * @param mixed $value
     * @param array $options
     * @return void
     * @throws ValidationException
     */
    public function validate(mixed $value, array $options = []): void;
}
