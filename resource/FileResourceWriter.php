<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource;

use Monoelf\Framework\resource\connection\DataBaseConnectionInterface;
use Monoelf\Framework\resource\ResourceWriterInterface;

final class FileResourceWriter implements ResourceWriterInterface
{
    private ?string $resourceName = null;

    private ?array $fieldNames = null;

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

        if ($this->fieldNames !== null) {
            $this->validateFieldsAccessible(array_keys($values));

            foreach ($this->fieldNames as $fieldName) {
                $values[$fieldName] = isset($values[$fieldName]) === true ?? null;
            }
        }

        return $this->databaseConnection->update(
            $this->resourceName,
            $values,
            ['id' => $id]
        );

    }

    public function patch(int|string $id, array $values): int
    {
        $this->validateSelfState();

        if ($this->fieldNames !== null) {
            $this->validateFieldsAccessible(array_keys($values));
        }

        return $this->databaseConnection->update(
            $this->resourceName,
            $values,
            ['id' => $id]
        );
    }

    public function delete(int|string $id): int
    {
        $this->validateSelfState();

        return $this->databaseConnection->delete(
            $this->resourceName,
            ['id' => $id]
        );
    }

    public function validateSelfState(): void
    {
        if ($this->resourceName === null) {
            throw new \InvalidArgumentException('Ресурс не задан');
        }
    }

    public function setAccessibleFields(array $fieldNames): static
    {
        $this->fieldNames = $fieldNames;

        return $this;
    }

    private function validateFieldsAccessible(array $fieldNames): void
    {
        foreach ($fieldNames as $fieldName) {
            if (in_array($fieldName, $this->fieldNames, true) === false) {
                throw new \InvalidArgumentException("Поле '{$fieldName}' недоступно для записи");
            }
        }
    }
}
