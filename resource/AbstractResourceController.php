<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource;

use InvalidArgumentException;
use Monoelf\Framework\event_dispatcher\EventDispatcherInterface;
use Monoelf\Framework\event_dispatcher\Message;
use Monoelf\Framework\http\response\CreateResponse;
use Monoelf\Framework\http\response\DeleteResponse;
use Monoelf\Framework\http\response\JsonResponse;
use Monoelf\Framework\http\response\PatchResponse;
use Monoelf\Framework\http\response\UpdateResponse;
use Monoelf\Framework\http\exceptions\HttpBadRequestException;
use Monoelf\Framework\http\exceptions\HttpForbiddenException;
use Monoelf\Framework\http\exceptions\HttpNotFoundException;
use Monoelf\Framework\resource\form_request\FormRequest;
use Monoelf\Framework\resource\form_request\FormRequestFactoryInterface;
use Monoelf\Framework\resource\form_request\FormRequestInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractResourceController
{
    public function __construct(
        protected ResourceDataFilterInterface $resourceDataFilter,
        protected ServerRequestInterface $request,
        protected FormRequestFactoryInterface $formRequestFactory,
        protected ResourceWriterInterface $resourceWriter,
        protected EventDispatcherInterface $eventDispatcher,
    ) {
        $this->resourceDataFilter
            ->setResourceName($this->getResourceName())
            ->setAccessibleFields($this->getAccessibleFields())
            ->setAccessibleFilters($this->getAccessibleFilters())
            ->setRelationships($this->getRelationships());

        $this->resourceWriter
            ->setResourceName($this->getResourceName())
            ->setAccessibleFields($this->getAccessibleFields())
            ->setRelationships($this->getRelationships());
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

    protected function getFieldRules(): array
    {
        return [];
    }

    protected function getRelationships(): array
    {
        return [];
    }

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
     * @throws HttpNotFoundException
     * @throws HttpForbiddenException
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
     * ?fields[]=id&fields[]=name
     * Пример ответа:
     * application/json
     * {
     *     "id": 1,
     *     "name": "Некоторое имя 1"
     * },
     *
     * @param int $id
     * @return JsonResponse
     * @throws HttpForbiddenException
     */
    public function actionView(int $id): JsonResponse
    {
        $this->checkCallAvailability(ResourceActionTypesEnum::VIEW);

        $conditions = $this->request->getQueryParams();
        $conditions['filter'] = ['id' => ['$eq' => $id]];

        $data = $this->resourceDataFilter->filterOne($conditions);

        return new JsonResponse($data);
    }

    /**
     * @throws HttpForbiddenException
     * @throws HttpBadRequestException
     */
    public function actionCreate(): CreateResponse
    {
        $this->checkCallAvailability(ResourceActionTypesEnum::CREATE);

        $form = $this->buildForm(ResourceActionTypesEnum::CREATE->value);

        $form->validate();

        if (empty($form->getErrors()) === false) {
            throw new HttpBadRequestException($form->getErrors());
        }

        try {
            $hasRelations = isset($this->request->getParsedBody()['relationships']) === true;

            if ($hasRelations === true) {
                $createdId = $this->resourceWriter->createWithRelated($form->getValues(), $this->request->getParsedBody()['relationships']);
            }

            if ($hasRelations === false) {
                $createdId = $this->resourceWriter->create($form->getValues());
            }

        } catch (InvalidArgumentException $exception) {
            throw new HttpBadRequestException($exception->getMessage());
        }

        $this->eventDispatcher->trigger(ResourceEvent::RESOURCE_CREATED, new Message([
            'resource' => $this->getResourceName(),
            'id' => $createdId,
        ]));

        return new CreateResponse($createdId);
    }

    /**
     * @throws HttpForbiddenException
     * @throws HttpBadRequestException
     * @throws HttpNotFoundException
     */
    public function actionUpdate(int $id): UpdateResponse
    {
        $this->checkCallAvailability(ResourceActionTypesEnum::UPDATE);

        $form = $this->buildForm(ResourceActionTypesEnum::UPDATE->value);

        $form->validate();

        if (empty($form->getErrors()) === false) {
            throw new HttpBadRequestException($form->getErrors());
        }

        try {
            $rowsCount = $this->resourceWriter->update($id, $form->getValues());
        } catch (InvalidArgumentException $exception) {
            throw new HttpBadRequestException($exception->getMessage());
        }

        if ($rowsCount === 0) {
            throw new HttpNotFoundException();
        }

        return new UpdateResponse();
    }

    /**
     * @throws HttpForbiddenException
     * @throws HttpBadRequestException
     * @throws HttpNotFoundException
     */
    public function actionPatch(int $id): PatchResponse
    {
        $this->checkCallAvailability(ResourceActionTypesEnum::PATCH);

        $form = $this->buildForm(ResourceActionTypesEnum::PATCH->value);

        $form->setSkipEmptyValues();

        $form->validate();

        if (empty($form->getErrors()) === false) {
            throw new HttpBadRequestException($form->getErrors());
        }

        try {
            $rowsCount = $this->resourceWriter->patch($id, $form->getValues());
        } catch (InvalidArgumentException $exception) {
            throw new HttpBadRequestException($exception->getMessage());
        }

        if ($rowsCount === 0) {
            throw new HttpNotFoundException();
        }

        return new PatchResponse();
    }

    /**
     * @throws HttpForbiddenException
     * @throws HttpNotFoundException
     */
    public function actionDelete(int $id): DeleteResponse
    {
        $this->checkCallAvailability(ResourceActionTypesEnum::DELETE);

        $rowsCount = $this->resourceWriter->delete($id);

        if ($rowsCount === 0) {
            throw new HttpNotFoundException();
        }

        return new DeleteResponse();
    }

    private function buildForm(string $action): FormRequestInterface
    {
        $formParams = $this->getForms()[$action] ?? null;

        if (is_array($formParams) === true && count($formParams) === 2) {
            return $this->formRequestFactory->create($formParams[0], $formParams[1]);
        }

        if (is_string($formParams) === true) {
            return $this->formRequestFactory->create($formParams);
        }

        throw new InvalidArgumentException('Форма должна быть задана либо строкой имя класса либо массивом [имя класса, набор правил]');
    }
}
