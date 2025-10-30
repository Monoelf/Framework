<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource\form_request;

use InvalidArgumentException;
use Monoelf\Framework\container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Monoelf\Framework\resource\form_request\FormRequestFactoryInterface;

final readonly class FormRequestFactory implements FormRequestFactoryInterface
{
    public function __construct(
        private ServerRequestInterface $request,
        private ContainerInterface $container,
    ) {}

    public function create(string $formClassName, array $rules = []): FormRequestInterface
    {
        if (is_subclass_of($formClassName, FormRequestInterface::class) === false) {
            throw new InvalidArgumentException(
                $formClassName . ' должен реализовывать интерфейс ' . FormRequestInterface::class
            );
        }

        $form = $this->container->get($formClassName);

        foreach ($rules as $rule) {
            if (count($rule) !== 2) {
                throw new InvalidArgumentException('Правила должны быть заданы в формате [[аттрибуты], правило]');
            }

            $form->addRule($rule[0], $rule[1]);
        }

        $data = $this->request->getParsedBody();

        foreach ($form->getFields() as $fieldName) {
            if (isset($data[$fieldName]) === false) {
                continue;
            }

            $form->setValue($fieldName, $data[$fieldName]);
        }

        return $form;
    }
}
