<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource\query\file;

use BadMethodCallException;
use InvalidArgumentException;
use Monoelf\Framework\resource\query\file\FileQueryBuilderInterface;
use Monoelf\Framework\resource\query\OperatorsEnum;

final class FileQueryBuilder implements FileQueryBuilderInterface
{
    private ?string $resource = null;
    private array $selectFields = [];
    private array $whereClause = [];
    private array $orderByClause = [];
    private ?int $limit = null;
    private ?int $offset = null;

    public function getStatement(): StatementParameters
    {
        return new StatementParameters(
            $this->resource,
            $this->selectFields,
            $this->whereClause,
            $this->orderByClause,
            $this->limit,
            $this->offset
        );
    }

    public function select(array|string $fields): static
    {
        $this->selectFields = is_string($fields) === true ? [$fields] : $fields;

        return $this;
    }

    public function from(array|string $resource): static
    {
        if (is_array($resource) === true) {
            throw new InvalidArgumentException('Ресурс должен быть строкой');
        }

        $this->resource = $resource;

        return $this;
    }

    public function where(array $condition): static
    {
        foreach ($condition as $field => $filterValue) {
            $filterValue = is_array($filterValue) === false
                ? [OperatorsEnum::EQ->value => $filterValue]
                : $filterValue;
            $this->whereClause[$field] = array_merge($this->whereClause[$field] ?? [], $filterValue);
        }

        return $this;
    }

    public function whereIn(string $column, array $values): static
    {
        $this->whereClause[$column][OperatorsEnum::IN->value] = $values;

        return $this;
    }

    public function join(string $type, array|string $resource, string $on): static
    {
        throw new BadMethodCallException('Join не реализуется для файлов');
    }

    public function orderBy(array $columns): static
    {
        foreach ($columns as $key => $value) {
            if (is_int($key) === true) {
                $this->orderByClause[$value] = 'asc';

                continue;
            }

            $this->orderByClause[$key] = $value;
        }

        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit < 0 ? null : $limit;

        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offset = $offset < 0 ? null : $offset;

        return $this;
    }

    public function reset(): static
    {
        $this->resource = null;
        $this->selectFields = [];
        $this->whereClause = [];
        $this->orderByClause = [];
        $this->limit = null;
        $this->offset = null;

        return $this;
    }
}
