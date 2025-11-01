<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource;

use InvalidArgumentException;
use Monoelf\Framework\resource\connection\DataBaseConnectionInterface;
use Monoelf\Framework\resource\ResourceWriterInterface;

final class DataBaseResourceWriter implements ResourceWriterInterface
{
    private string $resourceName = '';
    private array $accessibleFields = [];

    /**
     * @param DataBaseConnectionInterface $connection
     */
    public function __construct(
        private readonly DataBaseConnectionInterface $connection
    ) {}

    /**
     * @param string $name
     * @return $this
     */
    public function setResourceName(string $name): static
    {
        $this->resourceName = $name;

        return $this;
    }

    /**
     * @param array $values
     * @return int
     */
    public function create(array $values): int
    {
        return $this->connection->insert($this->resourceName, $values);
    }

    /**
     * @param string|int $id
     * @param array $values
     * @return int
     */
    public function update(string|int $id, array $values): int
    {
        $values = $this->prepareValues($values);

        $values['id'] = (int)$id;

        return $this->connection->update($this->resourceName, $values, ['id' => $id]);
    }

    /**
     * @param string|int $id
     * @param array $values
     * @return int
     */
    public function patch(string|int $id, array $values): int
    {
        $values['id'] = (int)$id;

        return $this->connection->update($this->resourceName, $values, ['id' => $id]);
    }

    /**
     * @param string|int $id
     * @return int
     */
    public function delete(string|int $id): int
    {
        return $this->connection->delete(
            $this->resourceName,
            ['id' => $id]
        );
    }

    /**
     * @param array $fieldNames
     * @return $this
     */
    public function setAccessibleFields(array $fieldNames): static
    {
        $this->accessibleFields = $fieldNames;

        return $this;
    }

    /**
     * @param array $values
     * @return array
     */
    private function prepareValues(array $values): array
    {
        foreach ($this->accessibleFields as $field) {
            if (array_key_exists($field, $values) === false) {
                $values[$field] = null;
            }
        }

        return $values;
    }
}
