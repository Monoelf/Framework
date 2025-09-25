<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource\connection;

use Monoelf\Framework\common\AliasManager;
use Monoelf\Framework\resource\connection\DataBaseConnectionInterface;
use Monoelf\Framework\resource\exceptions\InvalidQueryException;
use Monoelf\Framework\resource\query\file\FileQueryBuilderInterface;
use Monoelf\Framework\resource\query\file\StatementParameters;
use Monoelf\Framework\resource\query\QueryBuilderInterface;

final class FileDataBaseConnection implements DataBaseConnectionInterface
{
    private ?string $lastInsertId = null;

    public function __construct(
        private readonly AliasManager $aliasManager,
        string $resourcesPath = '@app/runtime/file-resources'
    ) {
        $this->aliasManager->addAlias('@file-resources', $resourcesPath);
    }

    public function select(QueryBuilderInterface $query): array
    {
        $statement = $this->getStatement($query);
        $filepath = $this->getFilepath($statement->resource);

        if (file_exists($filepath) === false) {
            return [];
        }

        $data = json_decode(file_get_contents($filepath), true, flags: JSON_THROW_ON_ERROR);

        if (is_array($data) === false) {
            return [];
        }

        return $this->applyQueryParameters($data, $statement);
    }

    public function selectOne(QueryBuilderInterface $query): null|array
    {
        $result = $this->select($query);

        return $result[0] ?? null;
    }

    public function selectColumn(QueryBuilderInterface $query): array
    {
        $statement = $this->getStatement($query);

        if (count($statement->selectFields) !== 1) {
            throw new InvalidQueryException('Запрос должен содержать только одно поле, переданно: ' . count($statement->selectFields));
        }

        $result = $this->select($query);

        return array_map(fn ($item) => $item[$statement->selectFields[0]] ?? null, $result);
    }

    public function selectScalar(QueryBuilderInterface $query): mixed
    {
        $result = $this->selectOne($query);

        if (is_array($result) === true) {
            return array_values($result)[0] ?? null;
        }

        return null;
    }

    public function update(string $resource, array $data, array $condition): int
    {
        $filepath = $this->getFilepath($resource);

        if (file_exists($filepath) === false) {
            return 0;
        }

        $existingData = json_decode(file_get_contents($filepath), true, flags: JSON_THROW_ON_ERROR);

        if (is_array($existingData) === false) {
            return 0;
        }

        $updatedCount = 0;

        foreach ($existingData as &$item) {
            if ($this->matchCondition($item, $condition) === true) {
                $item = array_merge($item, $data);

                $updatedCount++;
            }
        }

        file_put_contents($filepath, json_encode($existingData));

        return $updatedCount;
    }

    public function insert(string $resource, array $data): int
    {
        $filepath = $this->getFilepath($resource);

        $existingData = file_exists($filepath) === true
            ? json_decode(file_get_contents($filepath), true, flags: JSON_THROW_ON_ERROR)
            : [];

        if (is_array($existingData) === false) {
            $existingData = [];
        }

        $existingData[] = $data;

        $this->lastInsertId = (string)(count($existingData) - 1);

        file_put_contents($filepath, json_encode($existingData));

        return count($existingData) - 1;
    }

    public function delete(string $resource, array $condition): int
    {
        $filepath = $this->getFilepath($resource);

        if (file_exists($filepath) === false) {
            return 0;
        }

        $existingData = json_decode(file_get_contents($filepath), true, flags: JSON_THROW_ON_ERROR);

        if (is_array($existingData) === false) {
            return 0;
        }

        $filteredData = array_filter($existingData, fn ($item) => $this->matchCondition($item, $condition) === false);
        $deletedCount = count($existingData) - count($filteredData);

        file_put_contents($filepath, json_encode(array_values($filteredData)));

        return $deletedCount;
    }

    public function getLastInsertId(): string
    {
        return $this->lastInsertId;
    }

    private function getStatement(FileQueryBuilderInterface $queryBuilder): StatementParameters
    {
        return $queryBuilder->getStatement();
    }

    private function applyQueryParameters(array $data, StatementParameters $statement): array
    {
        if (empty($statement->whereClause) === false) {
            $data = array_filter($data, fn ($item) => $this->matchCondition($item, $statement->whereClause));
        }

        if (empty($statement->orderByClause) === false) {
            $data = $this->sortData($data, $statement->orderByClause);
        }

        if ($statement->limit !== null || $statement->offset !== null) {
            $data = array_slice($data, $statement->offset ?? 0, $statement->limit);
        }

        if (empty($statement->selectFields) === false) {
            $data = array_map(
                fn ($item) => array_intersect_key($item, array_flip($statement->selectFields)),
                $data
            );
        }

        return array_values($data);
    }

    private function matchCondition(array $item, array $condition): bool
    {
        foreach ($condition as $field => $value) {
            if (is_array($value) === false) {
                if (!in_array($item[$field] ?? null, $value, true)) {
                    return false;
                }
            } elseif (($item[$field] ?? null) != $value) {
                return false;
            }
        }
        return true;
    }

    private function sortData(array $data, array $orderBy): array
    {
        usort($data, function ($a, $b) use ($orderBy) {
            foreach ($orderBy as $field => $direction) {

                if (($a[$field] ?? null) == ($b[$field] ?? null)) {
                    continue;
                }

                $sortOrder = strtolower($direction) === 'desc' ? 1 : -1;

                return ($a[$field] ?? null) < ($b[$field] ?? null) ? $sortOrder : -$sortOrder;
            }

            return 0;
        });

        return $data;
    }

    private function getFilepath(string $resource): string
    {
        return $this->aliasManager->buildPath('@file-resources/' . $resource . '.json');
    }
}
