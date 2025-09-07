<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource;

interface ResourceDataFilterInterface
{
    public function setResourceName(string $name): static;

    public function setAccessibleFields(array $fieldNames): static;

    public function setAccessibleFilters(array $filterNames): static;

    /**
     * Возврат коллекции ресурсов, отфильтрованных в соответствии с условиями
     *
     * @param array $condition
     * Пример:
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
     * @return array
     * Пример:
     * [
     *     [
     *         "id" => 1,
     *         "order_id" => 3,
     *         "name" => "Некоторое имя 1"
     *     ],
     *     [
     *         "id" => 2,
     *         "order_id" => 3,
     *         "name" => "Некоторое имя 2"
     *     ],
     * ]
     */
    public function filterAll(array $condition): array;

    /**
     * Возврат ресурса, отфильтрованного в соответствии с условиями
     *
     * @param array $condition
     * Пример:
     * [
     *     "fields" => [
     *         "id",
     *         "name",
     *     ],
     *     "filter" => [
     *         "id" => [
     *             "$eq" => 1,
     *         ],
     *     ],
     * ]
     * @return array|null
     * Пример:
     * [
     *     "id" => 1,
     *     "name" => "Некоторое имя 1"
     * ],
     */
    public function filterOne(array $condition): array|null;
}
