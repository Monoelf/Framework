<?php

declare(strict_types=1);

namespace Monoelf\Framework\validator;

use Monoelf\Framework\resource\form_request\FormRequestInterface;

interface FormValidatorInterface
{
    /**
     * @param mixed $value
     * @param array $options
     * @return void
     * @throws ValidationException
     */
    public function validate(FormRequestInterface $form): void;
}
