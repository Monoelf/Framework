<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource\form_request;

interface FormRequestInterface
{
    public function rules(): array;

    public function addRule(array $attributes, array|string $rule): void;

    public function validate(): void;

    public function addError(string $attribute, string $message): void;

    public function getErrors(): array;

    public function setSkipEmptyValues(): void;

    public function getValues(): array;
}
