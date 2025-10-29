<?php

declare(strict_types=1);

namespace Monoelf\Framework\http;

use InvalidArgumentException;
use Monoelf\Framework\resource\connection\DataBaseConnectionInterface;
use Monoelf\Framework\resource\query\mySQL\DataBaseQueryBuilderInterface;
use Monoelf\Framework\resource\query\OperatorsEnum;
use Monoelf\Framework\resource\ResourceDataFilterInterface;
use RuntimeException;

final class DataBaseResourceDataFilter implements ResourceDataFilterInterface
{
    private string $resourceName;
    private array $accessibleFields = [];
    private array $accessibleFilters = [];

    public function __construct(
        private readonly DataBaseConnectionInterface $connection,
        private readonly DataBaseQueryBuilderInterface       $queryBuilder
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
     * @param array $fieldNames
     * @return $this
     */
    public function setAccessibleFields(array $fieldNames): static
    {
        $this->accessibleFields = $fieldNames;

        return $this;
    }

    /**
     * @param array $filterNames
     * @return $this
     */
    public function setAccessibleFilters(array $filterNames): static
    {
        $this->accessibleFilters = $filterNames;

        return $this;
    }

    /**
     * @param array $condition
     * @return array
     */
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

    /**
     * @param array $condition
     * @return array
     */
    private function extractConditionParts(array $condition): array
    {
        $fields = $condition['fields'] ?? [];
        $filters = $condition['filter'] ?? [];

        if (is_array($fields) === false || is_array($filters) === false) {
            throw new InvalidArgumentException('Поля и фильтры должны быть массивами');
        }

        return [$fields, $filters];
    }

    /**
     * @param array $filters
     * @return array
     */
    private function buildFilterConditions(array $filters): array
    {
        $conditions = [];

        foreach ($filters as $field => $operators) {
            if (is_array($operators) === false) {
                $conditions[$field] = $operators;
                continue;
            }

            foreach ($operators as $operator => $value) {
                $conditions = $this->appendOperatorCondition($conditions, $field, $operator, $value);
            }
        }

        return $conditions;
    }

    /**
     * @param array $conditions
     * @param string $field
     * @param string $operator
     * @param mixed $value
     * @return array
     */
    private function appendOperatorCondition(array $conditions, string $field, string $operator, mixed $value): array
    {
        $conditions[] = [
            'field' => $field,
            'operator' => match ($operator) {
                OperatorsEnum::EQ->value => '=',
                OperatorsEnum::NE->value => '!=',
                OperatorsEnum::GT->value => '>',
                OperatorsEnum::LT->value => '<',
                OperatorsEnum::GTE->value => '>=',
                OperatorsEnum::LTE->value => '<=',
                OperatorsEnum::LIKE->value => 'LIKE',
                OperatorsEnum::IN->value => 'IN',
                OperatorsEnum::NIN->value => 'NOT IN',
                default => throw new InvalidArgumentException("Неизвестный оператор: {$operator}")
            },
            'value' => $value
        ];

        return $conditions;
    }

    /**
     * @param array $fields
     * @return array|string[]
     */
    private function resolveSelectFields(array $fields): array
    {
        foreach ($fields as $field) {
            if (in_array($field, $this->accessibleFields, true) === false) {
                throw new InvalidArgumentException("Доступ к полю '{$field}' запрещён");
            }
        }

        return $fields;
    }

    /**
     * @param array $fields
     * @return void
     */
    private function validateFilters(array $fields): void
    {
        foreach ($fields as $field) {
            if (in_array($field, $this->accessibleFilters, true) === false) {
                throw new InvalidArgumentException("Фильтрация по полю '{$field}' недопустима");
            }
        }
    }
}