<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource;

use InvalidArgumentException;
use Monoelf\Framework\resource\connection\DataBaseConnectionInterface;
use Monoelf\Framework\resource\query\mySQL\DataBaseQueryBuilderInterface;
use Monoelf\Framework\resource\query\OperatorsEnum;
use Monoelf\Framework\resource\ResourceDataFilterInterface;

final class DataBaseResourceDataFilter implements ResourceDataFilterInterface
{
    private string $resourceName;
    private array $accessibleFields = [];
    private array $accessibleFilters = [];
    private array $relationships = [];

    public function __construct(
        private readonly DataBaseConnectionInterface $connection,
        private readonly DataBaseQueryBuilderInterface $queryBuilder
    ) {}

    public function setResourceName(string $name): static
    {
        $this->resourceName = $name;

        return $this;
    }

    public function setAccessibleFields(array $fieldNames): static
    {
        $this->accessibleFields = $fieldNames;

        return $this;
    }

    public function setAccessibleFilters(array $filterNames): static
    {
        $this->accessibleFilters = $filterNames;

        return $this;
    }

    public function setRelationships(array $relationships): static
    {
        $this->relationships = $relationships;

        return $this;
    }

    public function filterAll(array $condition): array
    {
        $this->prepareQuery($condition);

        $rows = $this->connection->select($this->queryBuilder);

        return $this->mapRelationshipsOnAll($rows, $condition['expand'] ?? []);
    }

    public function filterOne(array $condition): array|null
    {
        $this->prepareQuery($condition);

        $row = $this->connection->selectOne($this->queryBuilder);

        return $row !== null
            ? $this->mapRelationshipsOnOne($row, $condition['expand'] ?? [])
            : null;
    }

    private function mapRelationshipsOnAll(array $data, array $relationships): array
    {
        $result = [];

        foreach ($data as $item) {
            $result[] = $this->mapRelationshipsOnOne($item, $relationships);
        }

        return $result;
    }

    private function mapRelationshipsOnOne(array $item, array $relationships): array
    {
        $result = [];

        foreach ($item as $fieldName => $value) {
            if (str_contains($fieldName, '.') === false) {
                $result[$fieldName] = $value;

                continue;
            }

            [$relation, $key] = explode('.', $fieldName);

            if (in_array($relation, $relationships, true) === true) {
                $result['relationships'][$relation][$key] = $value;
            }
        }

        return $result;
    }

    private function extractConditionParts(array $condition): array
    {
        $fields = $condition['fields'] ?? [];
        $filters = $condition['filter'] ?? [];
        $relationships = $condition['expand'] ?? [];

        if (is_array($fields) === false || is_array($filters) === false || is_array($relationships) === false) {
            throw new InvalidArgumentException('Поля, фильтры и связи должны быть массивами');
        }

        $this->validateFields($fields);
        $this->validateFilters(array_keys($filters));
        $this->validateRelationships($relationships);

        if (empty($fields) === true) {
            $fields = $this->accessibleFields;
        }

        return [$fields, $filters, $relationships];
    }

    private function prepareJoins(array $joins): void
    {
        foreach ($joins as $relatedResource) {
            $relationRules = $this->relationships[$relatedResource];

            if (isset($relationRules['viaKey']) === true) {
                $this->queryBuilder->join('LEFT', $relatedResource, $this->buildJoinCondition(
                    $relatedResource,
                    $relationRules['table'],
                    $relationRules['viaKey']
                ));
            }

            $this->queryBuilder->join('LEFT', $relationRules['table'], $this->buildJoinCondition(
                $this->resourceName,
                $relationRules['table'],
                $relationRules['key']
            ));
        }
    }

    private function buildJoinCondition(string $originTable, string $targetTable, array|string $key): string
    {
        if (is_string($key) === true) {
            $key = ['id' => $key];
        }

        $originKey = array_key_first($key);
        $targetKey = $key[$originKey];

        return $originTable . '.' . $originKey . ' = ' . $targetTable . '.' . $targetKey;
    }

    private function validateFields(array $fields): void
    {
        foreach ($fields as $field) {
            if (in_array($field, $this->accessibleFields, true) === false) {
                throw new InvalidArgumentException("Доступ к полю '{$field}' запрещён");
            }
        }
    }

    private function validateFilters(array $fields): void
    {
        foreach ($fields as $field) {
            if (in_array($field, $this->accessibleFilters, true) === false) {
                throw new InvalidArgumentException("Фильтрация по полю '{$field}' недопустима");
            }
        }
    }

    private function validateRelationships(array $relationships): void
    {
        foreach ($relationships as $relationship) {
            if (array_key_exists($relationship, $this->relationships) === false) {
                throw new InvalidArgumentException("Связь с ресурсом {$relationship} не задана");
            }
        }
    }

    private function prepareQuery(array $conditions): void
    {
        [$fields, $filters, $relationships] = $this->extractConditionParts($conditions);

        $this->queryBuilder
            ->reset()
            ->select($fields)
            ->from($this->resourceName)
            ->where($filters);

        $this->prepareJoins($relationships);
    }
}
