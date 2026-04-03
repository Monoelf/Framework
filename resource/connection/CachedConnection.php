<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource\connection;

use Monoelf\Framework\queue\RedisClient;
use Monoelf\Framework\resource\connection\DataBaseConnectionInterface;
use Monoelf\Framework\resource\query\mySQL\DataBaseQueryBuilderInterface;
use Monoelf\Framework\resource\query\QueryBuilderInterface;

final readonly class CachedConnection implements DataBaseConnectionInterface
{
    public function __construct(
        private RedisClient $redis,
        private DataBaseConnectionInterface $databaseConnection,
        private string $tableName,
    ) {}

    public function select(QueryBuilderInterface $query): array
    {
        $cacheKey = $this->getCacheKeyByQuery($query);

        if ($cacheKey === null) {
            return $this->databaseConnection->select($query);
        }

        $selectKey = $this->getSelectKeyByQuery($cacheKey, $query, 'select');

        $result = $this->redis->get($selectKey);

        if ($result === null) {
            $result = $this->databaseConnection->select($query);
            $this->redis->set($selectKey, $result, 3600);
            $this->redis->addMember($cacheKey, $selectKey);
        }

        return $result;
    }

    public function selectOne(QueryBuilderInterface $query): null|array
    {
        $cacheKey = $this->getCacheKeyByQuery($query);

        if ($cacheKey === null) {
            return $this->databaseConnection->selectOne($query);
        }

        $selectKey = $this->getSelectKeyByQuery($cacheKey, $query, 'one');

        $result = $this->redis->get($selectKey);

        if ($result === null) {
            $result = $this->databaseConnection->selectOne($query);
            $this->redis->set($selectKey, $result, 3600);
            $this->redis->addMember($cacheKey, $selectKey);
        }

        return $result;
    }

    public function selectColumn(QueryBuilderInterface $query): array
    {
        $cacheKey = $this->getCacheKeyByQuery($query);

        if ($cacheKey === null) {
            return $this->databaseConnection->selectColumn($query);
        }

        $selectKey = $this->getSelectKeyByQuery($cacheKey, $query, 'column');

        $result = $this->redis->get($selectKey);

        if ($result === null) {
            $result = $this->databaseConnection->selectColumn($query);
            $this->redis->set($selectKey, $result, 3600);
            $this->redis->addMember($cacheKey, $selectKey);
        }

        return $result;
    }

    public function selectScalar(QueryBuilderInterface $query): mixed
    {
        $cacheKey = $this->getCacheKeyByQuery($query);

        if ($cacheKey === null) {
            return $this->databaseConnection->selectScalar($query);
        }

        $selectKey = $this->getSelectKeyByQuery($cacheKey, $query, 'scalar');

        $result = $this->redis->get($selectKey);

        if ($result === null) {
            $result = $this->databaseConnection->selectScalar($query);
            $this->redis->set($selectKey, $result, 3600);
            $this->redis->addMember($cacheKey, $selectKey);
        }

        return $result;
    }

    public function update(string $resource, array $data, array $condition): int
    {
        $cacheKey = $this->getCacheKeyByCondition($condition);

        if ($cacheKey !== null) {
            $this->redis->deleteMembers($cacheKey);
        }

        return $this->databaseConnection->update($resource, $data, $condition);
    }

    public function insert(string $resource, array $data): ?string
    {
        return $this->databaseConnection->insert($resource, $data);
    }

    public function delete(string $resource, array $condition): int
    {
        $cacheKey = $this->getCacheKeyByCondition($condition);

        if ($cacheKey !== null) {
            $this->redis->deleteMembers($cacheKey);
        }

        return $this->databaseConnection->delete($resource, $condition);
    }

    public function getLastInsertId(): string
    {
        return $this->databaseConnection->getLastInsertId();
    }

    public function beginTransaction(): void
    {
        $this->databaseConnection->beginTransaction();
    }

    public function commit(): void
    {
        $this->databaseConnection->commit();
    }

    public function rollBack(): void
    {
        $this->databaseConnection->rollBack();
    }

    private function getCacheKeyByQuery(DataBaseQueryBuilderInterface $queryBuilder): ?string
    {
        $statement = $queryBuilder->getStatement();

        if (
            count($statement->bindings) !== 1
            || str_contains($statement->sql, 'JOIN') === true
            || preg_match('/WHERE.*id/s', $statement->sql) !== 1
        ) {
            return null;
        }

        return $this->tableName . ':' . $statement->bindings['where_0'];
    }

    private function getSelectKeyByQuery(string $cacheKey, DataBaseQueryBuilderInterface $queryBuilder, string $method): string
    {
        return $cacheKey . ':' . sha1($queryBuilder->getStatement()->sql) . ':' . $method;
    }

    private function getCacheKeyByCondition(array $condition): ?string
    {
        if (isset($condition['id']) === false) {
            return null;
        }

        return $this->tableName . ':' . $condition['id'];
    }
}

