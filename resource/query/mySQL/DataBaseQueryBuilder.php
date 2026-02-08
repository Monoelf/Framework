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

    /**
     * @param array|string $fields
     * @return $this
     */
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


    /**
     * @param array|string $resource
     * @return $this
     */
    public function from(array|string $resource): static
    {
        if (is_array($resource) === true) {
            if (count($resource) !== 2) {
                throw new InvalidArgumentException("FROM с массивом должен содержать [table, alias]");
            }

            $table = $this->escapeField($resource[0]);

            $alias = $this->escapeField($resource[1]);

            $this->from = "FROM $table AS $alias";
            $this->originalResourceName = $resource[0];

            return $this;
        }

        $this->from = 'FROM ' . $this->escapeField($resource);
        $this->originalResourceName = $resource;

        return $this;
    }


    /**
     * @param array $condition
     * @return $this
     */
    public function where(array $condition): static
    {
        $condition = $this->buildFilterConditions($condition);

        $this->where = '';

        $this->bindings = [];

        $whereParts = [];

        foreach ($condition as $value) {
            if (is_array($value) === false || isset($value['field'], $value['operator'], $value['value']) === false) {
                continue;
            }

            $field = $this->escapeField($value['field']);

            if (in_array($value['operator'], ['IN', 'NOT IN']) === true) {
                $params = $this->buildParamsForArrayWhere($value['value']);
                $whereParts[] = "{$field} {$value['operator']} (" . implode(', ', $params) . ')';

                continue;
            }

            $param = 'where_' . count($this->bindings);
            $this->bindings[$param] = $value['value'];
            $whereParts[] = "{$field} {$value['operator']} :$param";
        }

        if (empty($whereParts) === false) {
            $this->where = 'WHERE ' . implode(' AND ', $whereParts);
        }

        return $this;
    }

    private function buildParamsForArrayWhere(array $values): array
    {
        $params = [];

        foreach ($values as $item) {
            $param = 'where_' . count($this->bindings);
            $params[] = ":$param";
            $this->bindings[$param] = $item;
        }

        return $params;
    }
    private function buildFilterConditions(array $filters): array
    {
        $conditions = [];

        foreach ($filters as $field => $operators) {
            $field = $this->buildFilterFieldName($field);

            if (is_array($operators) === false) {
                $conditions[] = [
                    'field' => $field,
                    'operator' => '=',
                    'value' => $operators,
                ];

                continue;
            }

            foreach ($operators as $operator => $value) {
                $conditions[] = $this->appendOperatorCondition($field, $operator, $value);
            }
        }

        return $conditions;
    }

    private function buildFilterFieldName(string $field): string
    {
        if (str_contains($field, '.') === false) {
            $field = $this->tmpResourceName . '.' . $field;
        }

        return $field;
    }

    private function appendOperatorCondition(string $field, string $operator, mixed $value): array
    {
        if ($operator === OperatorsEnum::LIKE->value) {
            $value = '%' . $value . '%';
        }

        return [
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
    }

    /**
     * @param string $column
     * @param array $values
     * @return $this
     */
    public function whereIn(string $column, array $values): static
    {
        $params = [];

        $escapedColumn = $this->escapeField($column);

        foreach ($values as $value) {
            $param = 'where_in_' . count($this->bindings);

            $params[] = ":$param";

            $this->bindings[$param] = $value;
        }

        $this->where = 'WHERE ' . $escapedColumn . ' IN (' . implode(', ', $params) . ')';

        return $this;
    }

    /**
     * @param string $type
     * @param string|array $resource
     * @param string $on
     * @return $this
     */
    public function join(string $type, string|array $resource, string $on): static
    {
        $type = strtoupper($type);
        if (in_array($type, ['INNER', 'LEFT', 'RIGHT', 'FULL']) === false) {
            throw new InvalidArgumentException("Некорректный тип JOIN'а");
        }

        if (is_array($resource) === true) {
            if (count($resource) !== 2) {
                throw new InvalidArgumentException("JOIN с массивом должен содержать [table, alias]");
            }

            $table = $this->escapeField($resource[0]);

            $alias = $this->escapeField($resource[1]);

            $this->joins[] = "$type JOIN $table AS $alias ON $on";

            return $this;
        }

        $table = $this->escapeField($resource);

        $this->joins[] = "$type JOIN $table ON $on";

        return $this;
    }

    /**
     * @param array $columns
     * @return $this
     */
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

    /**
     * @param int $limit
     * @return $this
     */
    public function limit(int $limit): static
    {
        $this->limit = 'LIMIT ' . $limit;

        return $this;
    }

    /**
     * @param int $offset
     * @return $this
     */
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

    /**
     * @param string $field
     * @param bool $isAlias
     * @return string
     */
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

    /**
     * @return string
     */
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