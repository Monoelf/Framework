<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource\form_request;

use InvalidArgumentException;
use Monoelf\Framework\resource\form_request\FormRequestInterface;
use Monoelf\Framework\validator\ValidationException;
use Monoelf\Framework\validator\Validator;

abstract class AbstractFormRequest implements FormRequestInterface
{
    protected bool $skipEmptyValues = false;
    private array $errors = [];
    private array $values = [];
    private array $dynamicRules = [];

    public function __construct(
        private readonly Validator $validator,
    ) {}

    /**
     * Возврат правил валидации формы
     *
     * @return array
     * Пример:
     * [
     *     [['name'], 'required'],
     *     [['name'], 'string'],
     * ]
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Динамическая установка правил валидации
     *
     * @param array $attributes
     * @param array|string $rule
     * @return void
     * Пример:
     * $form->addRule(['name'], 'required');
     */
    public function addRule(array $attributes, array|string $rule): void
    {
        $this->dynamicRules[] = [$attributes, $rule];
    }

    public function validate(): void
    {
        $values = $this->getValues();

        foreach ($this->getRules() as $rule) {
            if (count($rule) !== 2) {
                throw new InvalidArgumentException('Правила должны быть заданы в формате [[аттрибуты], правило]');
            }

            $this->validateByRule($values, $rule[0], $rule[1]);
        }
    }

    private function validateByRule(array $values, array $attributes, array|string $rule): void
    {
        foreach ($attributes as $attribute) {
            if (
                $this->skipEmptyValues === true
                && (array_key_exists($attribute, $values) === false
                    || $values[$attribute] === ''
                    || $values[$attribute] === null
                )
            ) {
                continue;
            }

            try {
                $this->validator->validate($values[$attribute] ?? null, $rule);
            } catch (ValidationException $e) {
                $this->addError($attribute, $e->getMessage());
            }
        }
    }

    public function addError(string $attribute, string $message): void
    {
        $this->errors[$attribute][] = $message;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function setSkipEmptyValues(): void
    {
        $this->skipEmptyValues = true;
    }

    /**
     * Возврат значений формы
     *
     * @return array
     * Пример:
     * [
     *     "id" => 1,
     *     "order_id" => 3,
     *     "name" => "Некоторое имя 1"
     * ]
     */
    public function getValues(): array
    {
        return $this->values;
    }

    public function setValue(string $name, mixed $value): void
    {
        $this->values[$name] = $value;
    }

    public function getFields(): array
    {
        return array_unique(array_merge(...array_column($this->getRules(), 0)));
    }

    protected function getRules(): array
    {
        return array_merge($this->rules(), $this->dynamicRules);
    }
}
