<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource;

use Monoelf\Framework\resource\connection\DataBaseConnectionInterface;
use Monoelf\Framework\resource\ResourceWriterInterface;

final class FileResourceWriter implements ResourceWriterInterface
{
    private ?string $resourceName = null;

    private ?array $accessibleField = null;

    public function __construct(
        private readonly DataBaseConnectionInterface $databaseConnection,
    ) {}

    public function setResourceName(string $name): static
    {
        $this->resourceName = $name;

        return $this;
    }

    public function create(array $values): int
    {
        $this->validateSelfState();

        return $this->databaseConnection->insert($this->resourceName, $values);
    }

    public function update(int|string $id, array $values): int
    {
        $this->validateSelfState();
        $this->validateFieldsAccessible(array_keys($values));

        $values['id'] = isset($values['id']) === true
            ? (int)$values['id']
            : $id;

        foreach ($this->accessibleField as $fieldName) {
            $values[$fieldName] = $values[$fieldName] ?? null;
        }

        return $this->databaseConnection->update(
            $this->resourceName,
            $values,
            ['id' => ['$eq' => $id]]
        );

    }

    public function patch(int|string $id, array $values): int
    {
        $this->validateSelfState();
        $this->validateFieldsAccessible(array_keys($values));

        return $this->databaseConnection->update(
            $this->resourceName,
            $values,
            ['id' => ['$eq' => $id]]
        );
    }

    public function delete(int|string $id): int
    {
        $this->validateSelfState();

        return $this->databaseConnection->delete(
            $this->resourceName,
            ['id' => ['$eq' => $id]]
        );
    }

    public function validateSelfState(): void
    {
        if ($this->resourceName === null) {
            throw new \InvalidArgumentException('Ресурс не задан');
        }

        if ($this->accessibleField === null) {
            throw new \InvalidArgumentException('Доступные поля не заданы');
        }
    }

    public function setAccessibleFields(array $fieldNames): static
    {
        $this->accessibleField = $fieldNames;

        return $this;
    }

    private function validateFieldsAccessible(array $fieldNames): void
    {
        foreach ($fieldNames as $fieldName) {
            if (in_array($fieldName, $this->accessibleField, true) === false) {
                throw new \InvalidArgumentException("Поле '{$fieldName}' недоступно для записи");
            }
        }
    }
}
