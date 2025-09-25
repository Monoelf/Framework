<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource\query\file;

use BadMethodCallException;
use Monoelf\Framework\resource\query\file\FileQueryBuilderInterface;

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
        // TODO: Уточнить, что значит массив $resource в контексте файлов
        $this->resource = is_string($resource) === true ? $resource : $resource[0];

        return $this;
    }

    public function where(array $condition): static
    {
        $this->whereClause = array_merge($this->whereClause, $condition);

        return $this;
    }

    public function whereIn(string $column, array $values): static
    {
        $this->whereClause[$column] = $values;

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
        $this->limit = $limit;

        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offset = $offset;

        return $this;
    }
}
