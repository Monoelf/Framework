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
    private array $relationships = [];

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

    public function setRelationships(array $relationships): static
    {
        $this->relationships = $relationships;

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

    public function createRelated(array $relationships): void
    {
        $this->validateRelationshipRules();

        foreach ($relationships as $relationName => $params) {
            $relationRules = $this->relationships[$relationName]
                ?? throw new InvalidArgumentException("Связь {$relationName} с не задана");

            if (isset($relationRules['viaKey']) === true) {
                [$originKey, $targetKey] = $this->getRelationKeys($relationRules['viaKey']);
                $values[$targetKey] = $params['data'][$originKey];
            }

            [, $targetKey] = $this->getRelationKeys($relationRules['key']);
            $values[$targetKey] = $this->connection->getLastInsertId();

            $this->connection->insert($relationRules['table'], $values);
        }
    }

    private function validateRelationshipRules(): void
    {
        foreach ($this->relationships as $relationName => $params) {
            if (isset($params['table']) === false) {
                throw new InvalidArgumentException("Для связи {$relationName} не задана таблица (table)");
            }

            if (isset($params['key']) === false) {
                throw new InvalidArgumentException("Для связи {$relationName} не задано правило связи ресурса с таблицей (key)");
            }

            if (isset($params['viaKey']) === false) {
                throw new InvalidArgumentException("Для связи {$relationName} не задано правило связи связанного ресурса с таблице (viaKey)");
            }
        }
    }

    private function getRelationKeys(array|string $relationKey): array
    {
        if (is_string($relationKey) === true) {
            $relationKey = ['id' => $relationKey];
        }

        $originKey = array_key_first($relationKey);
        $targetKey = $relationKey[$originKey];

        return [$originKey, $targetKey];
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
