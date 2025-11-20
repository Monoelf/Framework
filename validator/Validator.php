<?php

declare(strict_types=1);

namespace Monoelf\Framework\validator;

use Monoelf\Framework\container\ContainerInterface;
use Monoelf\Framework\container\DependencyNotFoundException;
use Monoelf\Framework\validator\rule_validators\BooleanValidator;
use Monoelf\Framework\validator\rule_validators\DateValidator;
use Monoelf\Framework\validator\rule_validators\EmailValidator;
use Monoelf\Framework\validator\rule_validators\ExistsValidator;
use Monoelf\Framework\validator\rule_validators\IntegerValidator;
use Monoelf\Framework\validator\rule_validators\RequiredValidator;
use Monoelf\Framework\validator\rule_validators\SafeValidator;
use Monoelf\Framework\validator\rule_validators\StringValidator;
use Monoelf\Framework\validator\rule_validators\UniqueValidator;

final class Validator
{
    private array $defaultConfig = [
        'int' => IntegerValidator::class,
        'integer' => IntegerValidator::class,
        'bool' => BooleanValidator::class,
        'boolean' => BooleanValidator::class,
        'string' => StringValidator::class,
        'required' => RequiredValidator::class,
        'safe' => SafeValidator::class,
        'date' => DateValidator::class,
        'email' => EmailValidator::class,
        'unique' => UniqueValidator::class,
        'exists' => ExistsValidator::class,
    ];

    private array $validators;

    public function __construct(
        private readonly ContainerInterface $container,
        array $validatorsConfig = [],
    ) {
        $this->validators = array_merge($this->defaultConfig, $validatorsConfig);
    }

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
        $options = [];

        if (is_array($rule) === true) {
            $ruleName = $rule[0] ?? throw new ValidatorNotSupportedException('Правило валидации не указано');
            $options = array_slice($rule, 1, null, true);

            if (
                isset($options['skipOnEmpty']) === true
                && is_null($value) === true
            ) {
                return;
            }
        }

        if (isset($this->validators[$ruleName]) === false) {
            throw new ValidatorNotSupportedException("Валидатор для правила '{$ruleName}' не задан");
        }

        if (is_subclass_of($this->validators[$ruleName], RuleValidatorInterface::class) === false) {
            throw new ValidatorNotSupportedException(
                'Валидатор для правила \''
                . $ruleName . '\' должен имплементировать интерфейс '
                . RuleValidatorInterface::class
            );
        }

        $this->container->get($this->validators[$ruleName])->validate($value, $options);
    }
}
