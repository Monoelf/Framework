<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource;

use InvalidArgumentException;
use Monoelf\Framework\resource\connection\DataBaseConnectionInterface;
use Monoelf\Framework\resource\ResourceWriterInterface;
use Throwable;

final class DataBaseResourceWriter implements ResourceWriterInterface
{
    private string $resourceName = '';
    private array $accessibleFields = [];

    public function __construct(
        private readonly DataBaseConnectionInterface $connection
    ) {}

    public function setResourceName(string $name): static
    {
        $this->resourceName = $name;

        return $this;
    }

    public function create(array $values): ?string
    {
        $this->validateFields(array_keys($values));

        return $this->connection->insert($this->resourceName, $values);
    }

    public function update(string|int $id, array $values): int
    {
        $this->validateFields(array_keys($values));

        $values = $this->prepareValues($values);

        $values['id'] = (int) $id;

        return $this->connection->update($this->resourceName, $values, ['id' => $id]);
    }

    public function patch(string|int $id, array $values): int
    {
        $this->validateFields(array_keys($values));

        $values['id'] = (int) $id;

        return $this->connection->update($this->resourceName, $values, ['id' => $id]);
    }

    public function delete(string|int $id): int
    {
        return $this->connection->delete(
            $this->resourceName,
            ['id' => $id],
        );
    }

    public function setAccessibleFields(array $fieldNames): static
    {
        $this->accessibleFields = $fieldNames;

        return $this;
    }

    private function prepareValues(array $values): array
    {
        foreach ($this->accessibleFields as $field) {
            if (array_key_exists($field, $values) === false) {
                $values[$field] = null;
            }
        }

        return $values;
    }

    private function validateFields(array $fieldNames): void
    {
        $notAllowedFields = array_diff($fieldNames, $this->accessibleFields);

        if (empty($notAllowedFields) === false) {
            throw new InvalidArgumentException('Запрещен доступ к полям: ' . implode(', ', $notAllowedFields));
        }
    }
}
