<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource\query\mySQL;

use InvalidArgumentException;

class MysqlQueryBuilder implements MysqlQueryBuilderInterface
{
    private string $select = '';
    private string $from = '';
    private string $where = '';
    private array $joins = [];
    private string $orderBy = '';
    private string $limit = '';
    private string $offset = '';
    private array $bindings = [];

    /**
     * @param array|string ...$fields
     * @return $this
     */
    public function select(array|string ...$fields): static
    {
        $fields = is_array($fields[0]) ? $fields[0] : $fields;

        $escapedFields = array_map(function ($field) {
            if (stripos($field, ' AS ') !== false) {
                list($original, $alias) = explode(' AS ', $field, 2);

                return $this->escapeField(trim($original)) . ' AS ' . $this->escapeField(trim($alias));
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

            return $this;
        }

        $this->from = 'FROM ' . $this->escapeField($resource);

        return $this;
    }


    /**
     * @param array $condition
     * @return $this
     */
    public function where(array $condition): static
    {
        $this->where = '';

        $this->bindings = [];

        $whereParts = [];

        foreach ($condition as $key => $value) {
            $param = 'where_' . count($this->bindings);

            $whereParts[] = $this->escapeField($key) . " = :$param";

            $this->bindings[$param] = $value;
        }

        if (empty($whereParts) === false) {
            $this->where = 'WHERE ' . implode(' AND ', $whereParts);
        }

        return $this;
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

            if ($isNumericArray && $isStringDirection) {
                $parts = preg_split('/\s+/', trim($direction), 2);

                $columnName = $parts[0];

                $dir = isset($parts[1]) ? strtoupper($parts[1]) : 'ASC';
            }

            if (false === $isNumericArray) {
                $columnName = $column;

                $dir = $isStringDirection ? strtoupper(trim($direction)) : 'ASC';
            }

            $isEmptyColumnName = empty($columnName);

            if ($isEmptyColumnName) {
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

        if ($hasOrderParts) {
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
        $sqlParts = [
            $this->select,
            $this->from,
            implode(' ', $this->joins),
            $this->where,
            $this->orderBy,
            $this->limit,
            $this->offset
        ];

        $sql = implode(' ', array_filter($sqlParts, fn($part) => empty($part) === false));

        return new StatementParameters($sql, $this->bindings);
    }

    /**
     * @param $field
     * @return string
     */
    private function escapeField($field): string
    {
        if ($field === '*') {
            return $field;
        }

        if (preg_match('/^[a-z]+\(.*\)$/i', $field)) {
            return $field;
        }

        if (str_contains($field, '.')) {
            $parts = explode('.', $field);

            return '`' . implode('`.`', array_map('trim', $parts)) . '`';
        }

        return '`' . str_replace('`', '``', $field) . '`';
    }

    /**
     * @return string
     */
    public function getRawSql(): string
    {
        $statement = $this->getStatement();

        $sql = $statement->getSql();

        $bindings = $statement->getBindings();

        foreach ($bindings as $param => $value) {
            $escapedValue = null;

            if (is_string($value)) {
                $escapedValue = "'" . str_replace("'", "''", $value) . "'";
            }

            if (is_null($value)) {
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