<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource;

use Monoelf\Framework\container\ContainerInterface;
use Monoelf\Framework\resource\connection\DataBaseConnectionInterface;
use Monoelf\Framework\resource\query\file\FileQueryBuilderInterface;

final class FileResourceDataFilter implements ResourceDataFilterInterface
{
    private ?string $resourceName = null;
    private ?array $accessibleFields = null;
    private ?array $accessibleFilters = null;

    public function __construct(
        private readonly DataBaseConnectionInterface $databaseConnection,
        private readonly ContainerInterface $container,
    ) {}


    public function setResourceName(string $name): static
    {
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

    /*
     * Пример:
     * @param array $condition
     * [
     *     "fields" => [
     *         "id",
     *         "order_id",
     *         "name",
     *     ],
     *     "filter" => [
     *         "order_id" => [
     *             "$eq" => 3,
     *         ],
     *     ],
     * ]
     */
    public function filterAll(array $condition): array
    {
        $this->checkConditionOnAccessible($condition);

        $query = $this->buildQuery($condition);

        return $this->databaseConnection->select($query);
    }

    public function filterOne(array $condition): array|null
    {
        $this->checkConditionOnAccessible($condition);

        $query = $this->buildQuery($condition);

        return $this->databaseConnection->selectOne($query);
    }

    private function checkConditionOnAccessible(array $condition): void
    {
        if (isset($condition['fields']) === false) {
            $condition['fields'] = $this->accessibleFields;
        }

        foreach ($condition['fields'] as $field) {
            if (in_array($field, $this->accessibleFields, true) === false) {
                throw new \InvalidArgumentException("Поле '{$field}' недоступно для выборки");
            }
        }

        if (isset($condition['filter']) === false) {
            return;
        }

        foreach ($condition['filter'] as $field => $filter) {
            if (in_array($field, $this->accessibleFilters, true) === false) {
                throw new \InvalidArgumentException("Фильтр по полю '{$field}' недоступен");
            }
        }
    }

    private function buildQuery(array $condition): FileQueryBuilderInterface
    {
        $builder = $this->container->get(FileQueryBuilderInterface::class);

        $builder->from($this->resourceName);

        if (empty($condition['fields']) === false) {
            $builder->select($condition['fields']);
        }

        if (empty($condition['filter']) === false) {
            $builder->where($condition['filter']);
        }

        if (empty($condition['order']) === false) {
            $builder->orderBy($condition['order']);
        }

        if (isset($condition['limit']) === true) {
            $builder->limit((int)$condition['limit']);
        }

        if (isset($condition['offset']) === true) {
            $builder->offset((int)$condition['offset']);
        }

        return $builder;
    }
}
