<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource\query\mySQL;

use InvalidArgumentException;
use Monoelf\Framework\resource\query\OperatorsEnum;

final class DataBaseQueryBuilder implements DataBaseQueryBuilderInterface
{
    private ?string $select = null;
    private ?string $from = null;
    private ?string $where = null;
    private array $joins = [];
    private ?string $orderBy = null;
    private ?string $limit = null;
    private ?string $offset = null;
    private array $bindings = [];
    private string $tmpResourceName = '$$$TMP_RESOURCE_NAME$$$';
    private ?string $originalResourceName = null;

    public function reset(): static
    {
        $this->select = null;
        $this->from = null;
        $this->where = null;
        $this->joins = [];
        $this->orderBy = null;
        $this->limit = null;
        $this->offset = null;
        $this->bindings = [];
        $this->originalResourceName = null;

        return $this;
    }

    public function select(array|string $fields): static
    {
        if (is_string($fields) === true) {
            $fields = [$fields];
        }

        $fields = $this->buildFields($fields);

        $escapedFields = array_map(function (string $field): string {
            if (stripos($field, ' AS ') !== false) {
                [$original, $alias] = explode(' AS ', $field, 2);
                return $this->escapeField(trim($original)) . ' AS ' . $this->escapeField(trim($alias), true);
            }

            return $this->escapeField($field);
        }, $fields);

        $this->select = 'SELECT ' . implode(', ', $escapedFields);

        return $this;
    }

    public function from(array|string $resource): static
    {
        if (is_string($resource) === true) {
            $this->from = 'FROM ' . $this->escapeField($resource);
            $this->originalResourceName = $resource;

            return $this;
        }

        if (count($resource) !== 1) {
            throw new InvalidArgumentException("FROM с массивом должен содержать [alias => table]");
        }

        $alias = array_key_first($resource);
        $table = $resource[$alias];

        $table = $this->escapeField($table);
        $alias = $this->escapeField($alias);

        $this->from = "FROM $table AS $alias";
        $this->originalResourceName = $table;

        return $this;
    }

    public function where(array $condition): static
    {
        $this->where = null;
        $this->bindings = [];

        $conditions = $this->buildFilterConditions($condition);

        if (empty($conditions) === true) {
            return $this;
        }

        $this->where = 'WHERE ' . implode(' AND ', $conditions);

        return $this;
    }

    private function buildFilterConditions(array $filters): array
    {
        $conditions = [];

        foreach ($filters as $field => $operators) {
            $field = $this->escapeField($this->buildFilterFieldName($field));

            if (is_array($operators) === false) {
                $operators = [OperatorsEnum::EQ->value => $operators];
            }

            foreach ($operators as $operator => $value) {
                $conditions[] = $this->buildConditionPart($field, $operator, $value);
            }
        }

        return $conditions;
    }

    private function buildConditionPart(string $field, string $operator, mixed $value): string
    {
        $sqlOperator = $this->getSqlOperator($operator);

        if ($value === null) {
            if ($operator === OperatorsEnum::EQ->value) {
                return "{$field} IS NULL";
            }

            if ($operator === OperatorsEnum::NE->value) {
                return "{$field} IS NOT NULL";
            }

            throw new InvalidArgumentException("Оператор {$operator} не поддерживает NULL");
        }

        if ($operator === OperatorsEnum::IN->value || $operator === OperatorsEnum::NIN->value) {
            if (empty($value) === true || is_array($value) === false) {
                throw new InvalidArgumentException("Оператор {$operator} требует непустой массив");
            }

            $params = [];

            foreach ($value as $item) {
                $param = 'where_' . count($this->bindings);
                $params[] = ":$param";
                $this->bindings[$param] = $item;
            }

            return "{$field} {$sqlOperator} (" . implode(', ', $params) . ")";
        }

        if ($operator === OperatorsEnum::LIKE->value) {
            $value = '%' . $value . '%';
        }

        $param = 'where_' . count($this->bindings);
        $this->bindings[$param] = $value;

        return "{$field} {$sqlOperator} :$param";
    }

    private function buildFilterFieldName(string $field): string
    {
        if (str_contains($field, '.') === false) {
            $field = $this->tmpResourceName . '.' . $field;
        }

        return $field;
    }

    private function getSqlOperator(string $operator): string
    {
        return match ($operator) {
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
        };
    }

    public function join(string $type, string|array $resource, string $on): static
    {
        $type = strtoupper($type);
        if (in_array($type, ['INNER', 'LEFT', 'RIGHT', 'FULL']) === false) {
            throw new InvalidArgumentException("Некорректный тип JOIN'а");
        }

        if (is_string($resource) === true) {
            $table = $this->escapeField($resource);

            $this->joins[] = "$type JOIN $table ON $on";

            return $this;
        }

        if (count($resource) !== 1) {
            throw new InvalidArgumentException("FROM с массивом должен содержать [alias => table]");
        }

        $alias = array_key_first($resource);
        $table = $resource[$alias];

        $table = $this->escapeField($table);
        $alias = $this->escapeField($alias);

        $this->joins[] = "$type JOIN $table AS $alias ON $on";

        return $this;
    }

    public function orderBy(array $columns): static
    {
        $orderParts = [];

        foreach ($columns as $column => $direction) {
            $columnName = '';

            $dir = 'ASC';

            $isNumericArray = is_int($column);

            $isStringDirection = is_string($direction);

            if ($isNumericArray === true && $isStringDirection === true) {
                $parts = preg_split('/\s+/', trim($direction), 2);

                $columnName = $parts[0];

                $dir = isset($parts[1]) ? strtoupper($parts[1]) : 'ASC';
            }

            if ($isNumericArray === false) {
                $columnName = $column;

                $dir = $isStringDirection ? strtoupper(trim($direction)) : 'ASC';
            }

            $isEmptyColumnName = empty($columnName);

            if ($isEmptyColumnName === true) {
                throw new InvalidArgumentException('Имя колонны должно быть заполнено');
            }

            $isValidDirection = in_array($dir, ['ASC', 'DESC']);

            if (false === $isValidDirection) {
                throw new InvalidArgumentException('Некорректный формат распределения');
            }

            $escapedColumn = $this->escapeField($columnName);

            $orderParts[] = "$escapedColumn $dir";
        }

        $hasOrderParts = empty($orderParts) === false;

        if ($hasOrderParts === true) {
            $this->orderBy = 'ORDER BY ' . implode(', ', $orderParts);
        }

        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = 'LIMIT ' . $limit;

        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offset = 'OFFSET ' . $offset;

        return $this;
    }

    /**
     * @return StatementParameters
     */
    public function getStatement(): StatementParameters
    {
        if ($this->originalResourceName === null) {
            throw new InvalidArgumentException('Имя ресурса не задано');
        }

        $sqlParts = [
            $this->select,
            $this->from,
            empty($this->joins) ? null : implode(' ', $this->joins),
            $this->where,
            $this->orderBy,
            $this->limit,
            $this->offset,
        ];

        $sqlParts = array_filter(
            $sqlParts,
            static fn (?string $part): bool => $part !== null && $part !== ''
        );

        $sql = str_replace($this->tmpResourceName, $this->originalResourceName, implode(' ', $sqlParts));

        return new StatementParameters($sql, $this->bindings);
    }

    private function buildFieldName(string $field): string
    {
        return str_contains($field, '.') === true
            ? $field . ' AS ' . $field
            : $this->tmpResourceName . '.' . $field;
    }

    private function buildFields(array $fields): array
    {
        $result = [];

        foreach ($fields as $field) {
            $result[] = $this->buildFieldName($field);
        }

        return $result;
    }

    private function escapeField(string $field, bool $isAlias = false): string
    {
        if ($field === '*') {
            return $field;
        }

        if (str_contains($field, '.') === true && $isAlias === false) {
            $parts = explode('.', $field);
            $preparedParts = array_map(fn (string $part): string => $this->escapeField(trim($part)), $parts);

            return implode('.', $preparedParts);
        }

        return '`' . str_replace('`', '``', $field) . '`';
    }

    public function getRawSql(): string
    {
        $statement = $this->getStatement();

        $sql = $statement->sql;

        foreach ($statement->bindings as $param => $value) {
            $escapedValue = null;

            if (is_string($value) === true) {
                $escapedValue = "'" . str_replace("'", "''", $value) . "'";
            }

            if (is_null($value) === true) {
                $escapedValue = 'NULL';
            }

            if ($escapedValue === null) {
                $escapedValue = (string)$value;
            }

            $sql = str_replace(':' . $param, $escapedValue, $sql);
        }

        return $sql;
    }
}