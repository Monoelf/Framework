<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource\form_request;

interface FormRequestFactoryInterface
{
    public function create(string $formClassName): FormRequestInterface;
}
