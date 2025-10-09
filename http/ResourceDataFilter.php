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
        private readonly QueryBuilderInterface $queryBuilder
    ) {}

    /**
     * @param string $name
     * @return $this
     */
    public function setResourceName(string $name): static
    {
        if (empty($name) === true) {
            throw new InvalidArgumentException('Имя ресурса должно быть заполнено');
        }

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
    public function filterAll(array $condition = []): array
    {
        $this->validateResource();
        $conditions = $this->mergeConditions($condition);
        $this->validateFilters($conditions);

        $query = $this->buildBaseQuery()
            ->where($conditions);

        return $this->connection->select($query);
    }

    /**
     * @param array $condition
     * @return array
     */
    public function filterOne(array $condition = []): array
    {
        $this->validateResource();
        $conditions = $this->mergeConditions($condition);
        $this->validateFilters($conditions);

        $query = $this->buildBaseQuery()
            ->where($conditions);

        $result = $this->connection->selectOne($query);

        return $result ?? [];
    }

    /**
     * @return QueryBuilderInterface
     */
    private function buildBaseQuery(): QueryBuilderInterface
    {
        return $this->queryBuilder
            ->select($this->getSelectFields())
            ->from($this->resourceName);
    }

    /**
     * @return array|string[]
     */
    private function getSelectFields(): array
    {
        return empty($this->accessibleFields) ? ['*'] : $this->accessibleFields;
    }

    /**
     * @return void
     */
    private function validateResource(): void
    {
        if (empty($this->resourceName) === true) {
            throw new RuntimeException('Ресурс не задан');
        }
    }

    /**
     * @param array $conditions
     * @return void
     */
    private function validateFilters(array $conditions): void
    {
        if (empty($this->accessibleFilters) === true) {
            return;
        }

        foreach (array_keys($conditions) as $field) {
            if (in_array($field, $this->accessibleFilters, true) === false) {
                throw new InvalidArgumentException(
                    sprintf('Фильтрация поля "%s" недопустима', $field)
                );
            }
        }
    }

    /**
     * @param array $conditions
     * @return array
     */
    private function mergeConditions(array $conditions): array
    {
        return array_merge($this->defaultConditions, $conditions);
    }
}