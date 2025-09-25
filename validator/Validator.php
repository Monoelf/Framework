<?php

declare(strict_types=1);

namespace Monoelf\Framework\validator;

final readonly class Validator
{
    public function __construct(
        /**
         * @var array<string, RuleValidatorInterface> $validators
         */
        private array $validators,
    ) {}

    /**
     * @param mixed $value
     * @param string|array $rule
     * @return void
     * @throws ValidationException
     * @throws ValidatorNotSupportedException
     */
    public function validate(mixed $value, string|array $rule): void
    {
        $ruleName = $rule;
        $options  = [];

        if (is_array($rule) === true) {
            $ruleName = $rule[0] ?? throw new ValidatorNotSupportedException('Правило валидации не указано');
            $options  = array_slice($rule, 1, null, true);
        }

        if (isset($this->validators[$ruleName]) === false) {
            throw new ValidatorNotSupportedException("Валидатор для правила '{$ruleName}' не задан");
        }

        if ($this->validators[$ruleName] instanceof RuleValidatorInterface === false) {
            throw new ValidatorNotSupportedException(
                'Валидатор для правила \''
                . $ruleName . '\' должен имплементировать интерфейс '
                . RuleValidatorInterface::class
            );
        }

        $this->validators[$ruleName]->validate($value, $options);
    }
}
