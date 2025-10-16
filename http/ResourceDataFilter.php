<?php

declare(strict_types=1);

namespace Monoelf\Framework\http;

use InvalidArgumentException;
use Monoelf\Framework\resource\connection\DataBaseConnectionInterface;
use Monoelf\Framework\resource\query\QueryBuilderInterface;
use Monoelf\Framework\resource\ResourceDataFilterInterface;
use RuntimeException;

final class ResourceDataFilter implements ResourceDataFilterInterface
{
    private string $resourceName;
    private array $accessibleFields = [];
    private array $accessibleFilters = [];
    private array $defaultConditions = [];

    public function __construct(
        private readonly DataBaseConnectionInterface $connection,
        private readonly QueryBuilderInterface       $queryBuilder
    )
    {
    }

    public function setResourceName(string $name): static
    {
        if ($name === '') {
            throw new InvalidArgumentException('Имя ресурса должно быть указано');
        }

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

    public function filterAll(array $condition): array
    {
        [$fields, $filters] = $this->extractConditionParts($condition);
        $this->validateFilters(array_keys($filters));

        $query = $this->queryBuilder
            ->select($this->resolveSelectFields($fields))
            ->from($this->resourceName)
            ->where($this->buildFilterConditions($filters));

        return $this->connection->select($query);
    }

    public function filterOne(array $condition): array|null
    {
        [$fields, $filters] = $this->extractConditionParts($condition);
        $this->validateFilters(array_keys($filters));

        $query = $this->queryBuilder
            ->select($this->resolveSelectFields($fields))
            ->from($this->resourceName)
            ->where($this->buildFilterConditions($filters));

        return $this->connection->selectOne($query);
    }

    private function extractConditionParts(array $condition): array
    {
        $fields = $condition['fields'] ?? [];
        $filters = $condition['filter'] ?? [];

        if (!is_array($fields) || !is_array($filters)) {
            throw new InvalidArgumentException('Поля и фильтры должны быть массивами');
        }

        return [$fields, array_merge($this->defaultConditions, $filters)];
    }

    private function buildFilterConditions(array $filters): array
    {
        $conditions = [];

        foreach ($filters as $field => $operators) {
            if (is_array($operators) === false) {
                $conditions[$field] = $operators;
                continue;
            }

            foreach ($operators as $operator => $value) {
                $this->appendOperatorCondition($conditions, $field, $operator, $value);
            }
        }

        return $conditions;
    }

    private function appendOperatorCondition(array &$conditions, string $field, string $operator, mixed $value): void
    {
        $conditions[] = [
            'field' => $field,
            'operator' => match ($operator) {
                '$eq' => '=',
                '$ne' => '!=',
                '$gt' => '>',
                '$lt' => '<',
                '$gte' => '>=',
                '$lte' => '<=',
                '$like' => 'LIKE',
                '$in' => 'IN',
                default => throw new InvalidArgumentException("Неизвестный оператор: {$operator}")
            },
            'value' => $value
        ];
    }

    private function resolveSelectFields(array $fields): array
    {
        if (empty($fields)) {
            return $this->accessibleFields ?: ['*'];
        }

        foreach ($fields as $field) {
            if (!in_array($field, $this->accessibleFields, true)) {
                throw new InvalidArgumentException("Доступ к полю '{$field}' запрещён");
            }
        }

        return $fields;
    }

    private function validateFilters(array $fields): void
    {
        if (empty($this->accessibleFilters)) {
            return;
        }

        foreach ($fields as $field) {
            if (!in_array($field, $this->accessibleFilters, true)) {
                throw new InvalidArgumentException("Фильтрация по полю '{$field}' недопустима");
            }
        }
    }
}