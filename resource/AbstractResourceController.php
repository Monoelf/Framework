<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource;

use Monoelf\Framework\http\dto\CreateResponse;
use Monoelf\Framework\http\dto\DeleteResponse;
use Monoelf\Framework\http\dto\JsonResponse;
use Monoelf\Framework\http\dto\PatchResponse;
use Monoelf\Framework\http\dto\UpdateResponse;
use Monoelf\Framework\http\exceptions\HttpBadRequestException;
use Monoelf\Framework\http\exceptions\HttpForbiddenException;
use Monoelf\Framework\http\exceptions\HttpNotFoundException;
use Monoelf\Framework\resource\form_request\FormRequest;
use Monoelf\Framework\resource\form_request\FormRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractResourceController
{
    public function __construct(
        protected ResourceDataFilterInterface $resourceDataFilter,
        protected ServerRequestInterface $request,
        protected FormRequestFactoryInterface $formRequestFactory,
        protected ResourceWriterInterface $resourceWriter,
    ) {
        $this->resourceDataFilter
            ->setResourceName($this->getResourceName())
            ->setAccessibleFields($this->getAccessibleFilters())
            ->setAccessibleFilters($this->getAccessibleFields());

        $this->resourceWriter
            ->setResourceName($this->getResourceName())
            ->setAccessibleFields($this->getAccessibleFilters());
    }

    protected function getForms(): array
    {
        return [
            ResourceActionTypesEnum::CREATE->value => [FormRequest::class, $this->getFieldRules()],
            ResourceActionTypesEnum::UPDATE->value => [FormRequest::class, $this->getFieldRules()],
            ResourceActionTypesEnum::PATCH->value => [FormRequest::class, $this->getFieldRules()],
        ];
    }

    protected function getAvailableActions(): array
    {
        return [
            ResourceActionTypesEnum::INDEX,
            ResourceActionTypesEnum::VIEW,
            ResourceActionTypesEnum::CREATE,
            ResourceActionTypesEnum::UPDATE,
            ResourceActionTypesEnum::PATCH,
            ResourceActionTypesEnum::DELETE,
        ];
    }

    abstract protected function getFieldRules(): array;

    abstract protected function getResourceName(): string;

    /**
     * Возврат имен свойств ресурса, доступных к чтению
     * Пример запроса:
     * ?fields=id,order_id,name
     *
     * @return array
     */
    abstract protected function getAccessibleFields(): array;

    /**
     * Возврат имен свойств ресурса, доступных к фильтрации
     * Пример запроса:
     * ?filter[order_id][$eq]=3
     *
     * @return array
     */
    abstract protected function getAccessibleFilters(): array;

    /**
     * @throws HttpForbiddenException
     */
    private function checkCallAvailability(ResourceActionTypesEnum $actionType): void
    {
        if (in_array($actionType, $this->getAvailableActions(), true) === false) {
            throw new HttpForbiddenException("Метод {$actionType->value} запрещен");
        }
    }

    /**
     * Возврат ресурсов, по ограничениям указанным в строке запроса
     * Пример запроса:
     * ?fields[]=id&fields[]=order_id&fields[]=name&filter[order_id][$eq]=3
     * Пример ответа:
     * application/json
     * [
     *     {
     *         "id": 1,
     *         "order_id":3,
     *         "name": "Некоторое имя 1"
     *     },
     *     {
     *         "id": 2,
     *         "order_id":3,
     *         "name": "Некоторое имя 2"
     *     },
     *     ...
     * ]
     *
     * @return JsonResponse
     */
    public function actionList(): JsonResponse
    {
        $this->checkCallAvailability(ResourceActionTypesEnum::INDEX);

        $data = $this->resourceDataFilter->filterAll($this->request->getQueryParams());

        return new JsonResponse($data);
    }

    /**
     * Возврат ресурса, по ограничениям указанным в строке запроса
     * Пример запроса:
     * ?fields[]=id&fields[]=name&filter[id][$eq]=1
     * Пример ответа:
     * application/json
     * {
     *     "id": 1,
     *     "name": "Некоторое имя 1"
     * },
     *
     * @return JsonResponse
     */
    public function actionView(): JsonResponse
    {
        $this->checkCallAvailability(ResourceActionTypesEnum::VIEW);

        $data = $this->resourceDataFilter->filterOne($this->request->getQueryParams());

        return new JsonResponse($data);
    }

    public function actionCreate(): CreateResponse
    {
        $this->checkCallAvailability(ResourceActionTypesEnum::CREATE);

        $form = $this->formRequestFactory->create(...$this->getForms()[ResourceActionTypesEnum::CREATE->value]);

        $form->validate();

        if (empty($form->getErrors()) === false) {
            throw new HttpBadRequestException(json_encode($form->getErrors(), JSON_UNESCAPED_UNICODE));
        }

        $this->resourceWriter->create($form->getValues());

        return new CreateResponse();
    }

    public function actionUpdate(string|int $id): UpdateResponse
    {
        $this->checkCallAvailability(ResourceActionTypesEnum::UPDATE);

        $form = $this->formRequestFactory->create(...$this->getForms()[ResourceActionTypesEnum::UPDATE->value]);

        $form->validate();

        if (empty($form->getErrors()) === false) {
            throw new HttpBadRequestException(json_encode($form->getErrors(), JSON_UNESCAPED_UNICODE));
        }

        $rowsCount = $this->resourceWriter->update($id, $form->getValues());

        if ($rowsCount === 0) {
            throw new HttpNotFoundException();
        }

        return new UpdateResponse();
    }

    public function actionPatch(string|int $id): PatchResponse
    {
        $this->checkCallAvailability(ResourceActionTypesEnum::PATCH);

        $form = $this->formRequestFactory->create(...$this->getForms()[ResourceActionTypesEnum::PATCH->value]);

        $form->setSkipEmptyValues();

        $form->validate();

        if (empty($form->getErrors()) === false) {
            throw new HttpBadRequestException(json_encode($form->getErrors(), JSON_UNESCAPED_UNICODE));
        }

        $rowsCount = $this->resourceWriter->patch($id, $form->getValues());

        if ($rowsCount === 0) {
            throw new HttpNotFoundException();
        }

        return new PatchResponse();
    }

    public function actionDelete(string|int $id): DeleteResponse
    {
        $this->checkCallAvailability(ResourceActionTypesEnum::DELETE);

        $rowsCount = $this->resourceWriter->delete($id);

        if ($rowsCount === 0) {
            throw new HttpNotFoundException();
        }

        return new DeleteResponse();
    }
}
