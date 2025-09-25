<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource;

use Monoelf\Framework\resource\query\file\FileQueryBuilderInterface;

final class FileResourceDataFilter implements ResourceDataFilterInterface
{
    private ?string $resourceName = null;
    private ?array $accessibleFields = null;
    private ?array $accessibleFilters = null;

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
    public function filterAll(array $condition): array {
        $this->checkConditionOnAccessible($condition);

        // TODO:  и тут поиск данных
    }

    public function filterOne(array $condition): array|null
    {
        $this->checkConditionOnAccessible($condition);

        // TODO:  и тут поиск данных
    }

    private function checkConditionOnAccessible(array $condition): void
    {
        foreach ($condition['fields'] as $field) {
            if (in_array($field, $this->accessibleFields, true) === false) {
                throw new \InvalidArgumentException("Поле '{$field}' недоступно для выборки");
            }
        }

        foreach ($condition['filter'] as $field => $filter) {
            if (!in_array($field, $this->accessibleFilters, true) === false) {
                throw new \InvalidArgumentException("Фильтр по полю '{$field}' недоступен");
            }
        }
    }
}
