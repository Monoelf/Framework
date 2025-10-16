<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource\connection;

use Monoelf\Framework\common\AliasManager;
use Monoelf\Framework\resource\connection\DataBaseConnectionInterface;
use Monoelf\Framework\resource\exceptions\FileNotExistsException;
use Monoelf\Framework\resource\exceptions\InvalidQueryException;
use Monoelf\Framework\resource\query\file\FileQueryBuilderInterface;
use Monoelf\Framework\resource\query\file\StatementParameters;
use Monoelf\Framework\resource\query\OperatorsEnum;
use Monoelf\Framework\resource\query\QueryBuilderInterface;

final class JsonDataBaseConnection implements DataBaseConnectionInterface
{
    private ?string $lastInsertId = null;
    private array $operators;

    public function __construct(
        private readonly AliasManager $aliasManager,
        string $resourcesPath = '@app/runtime',
    ) {
        $this->aliasManager->addAlias('@file-resources', $resourcesPath);
        $this->operators = [
            OperatorsEnum::EQ->value => fn (mixed $item, mixed $compare): bool => $item === $compare,
            OperatorsEnum::NE->value => fn (mixed $item, mixed $compare): bool => $item !== $compare,
            OperatorsEnum::GT->value => fn (mixed $item, mixed $compare): bool => $item > $compare,
            OperatorsEnum::GTE->value => fn (mixed $item, mixed $compare): bool => $item >= $compare,
            OperatorsEnum::LT->value => fn (mixed $item, mixed $compare): bool => $item < $compare,
            OperatorsEnum::LTE->value => fn (mixed $item, mixed $compare): bool => $item <= $compare,
            OperatorsEnum::IN->value => fn (mixed $item, array $compare): bool => in_array($item, $compare, true) === true,
            OperatorsEnum::NIN->value => fn (mixed $item, array $compare): bool => in_array($item, $compare, true) === false,
            OperatorsEnum::LIKE->value => fn (string $item, string $compare): bool => str_contains($item, $compare) === true,
        ];
    }

    /**
     * @throws FileNotExistsException
     * @throws \JsonException
     */
    public function select(QueryBuilderInterface $query): array
    {
        $statement = $this->getStatement($query);
        $filepath = $this->getFilepath($statement->resource);

        $data = json_decode(file_get_contents($filepath), true, flags: JSON_THROW_ON_ERROR);

        return $this->applyQueryParameters($data, $statement);
    }

    /**
     * @throws FileNotExistsException
     * @throws \JsonException
     */
    public function selectOne(QueryBuilderInterface $query): null|array
    {
        return $this->select($query)[0] ?? null;
    }

    /**
     * @throws FileNotExistsException
     * @throws InvalidQueryException
     * @throws \JsonException
     */
    public function selectColumn(QueryBuilderInterface $query): array
    {
        $statement = $this->getStatement($query);

        if (count($statement->selectFields) !== 1) {
            throw new InvalidQueryException('Запрос должен содержать только одно поле, переданно: ' . count($statement->selectFields));
        }

        $result = $this->select($query);

        return array_map(fn ($item) => $item[$statement->selectFields[0]] ?? null, $result);
    }

    /**
     * @throws FileNotExistsException
     * @throws \JsonException
     */
    public function selectScalar(QueryBuilderInterface $query): mixed
    {
        $result = $this->selectOne($query);

        if ($result === null) {
            return null;
        }

        return array_values($result)[0] ?? null;
    }

    /**
     * @throws FileNotExistsException
     * @throws \JsonException
     */
    public function update(string $resource, array $data, array $condition): int
    {
        $filepath = $this->getFilepath($resource);

        $existingData = json_decode(file_get_contents($filepath), true, flags: JSON_THROW_ON_ERROR);

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

    /**
     * @throws FileNotExistsException
     * @throws \JsonException
     */
    public function insert(string $resource, array $data): int
    {
        $filepath = $this->getFilepath($resource);

        $existingData = json_decode(file_get_contents($filepath), true, flags: JSON_THROW_ON_ERROR);

        $data['id'] = isset($data['id']) === true
            ? (int) $data['id']
            : ($this->loadLastInsertedId($resource) + 1);
        $existingData[] = $data;

        file_put_contents($filepath, json_encode($existingData));

        $this->saveLastInsertedId($resource, $data['id']);

        return 1;
    }

    /**
     * @throws FileNotExistsException
     * @throws \JsonException
     */
    public function delete(string $resource, array $condition): int
    {
        $filepath = $this->getFilepath($resource);

        $existingData = json_decode(file_get_contents($filepath), true, flags: JSON_THROW_ON_ERROR);

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
            $flippedSelectFields = array_flip($statement->selectFields);

            $data = array_map(
                fn (array $item): array => array_intersect_key($item, $flippedSelectFields),
                $data
            );
        }

        return array_values($data);
    }

    /*
     * [
     *  'field1' => [
     *      '$in' => [...]
     * ],
     * 'field2' => 123
     */

    private function matchCondition(array $item, array $condition): bool
    {
        foreach ($condition as $field => $filterValue) {
            $itemValue = $item[$field] ?? null;

            if ($this->matchArrayCondition($filterValue, $itemValue) === false) {
                return false;
            }

        }

        return true;
    }

    private function matchArrayCondition(array $fieldFilters, mixed $itemValue): bool
    {
        foreach ($fieldFilters as $op => $value) {
            if (isset($this->operators[$op]) === false) {
                throw new \InvalidArgumentException("Оператор {$op} не поддерживается");
            }

            if ($this->operators[$op]($itemValue, $value) === false) {
                return false;
            }
        }

        return true;
    }

    private function sortData(array $data, array $orderBy): array
    {
        usort($data, function (array $firstItem, array $secondItem) use ($orderBy): int {
            foreach ($orderBy as $field => $direction) {
                if (($firstItem[$field] ?? null) === ($secondItem[$field] ?? null)) {
                    continue;
                }

                $sortOrder = strtolower($direction) === 'desc' ? 1 : -1;

                return ($firstItem[$field] ?? null) < ($secondItem[$field] ?? null) ? $sortOrder : -$sortOrder;
            }

            return 0;
        });

        return $data;
    }

    /**
     * @throws FileNotExistsException
     */
    private function getFilepath(string $resource): string
    {
        $filepath = $this->aliasManager->buildPath('@file-resources/' . $resource . '.json');

        if (file_exists($filepath) === false) {
            throw new FileNotExistsException("Файл $filepath не существует");
        }

        return $filepath;
    }

    /**
     * @throws \JsonException
     */
    private function saveLastInsertedId(string $resource, int $id): void
    {
        $filepathMeta = $this->aliasManager->buildPath('@file-resources/_meta.json');

        if (file_exists($filepathMeta) === false) {
            $dir = dirname($filepathMeta);

            if (is_dir($dir) === false) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($filepathMeta, json_encode([], JSON_UNESCAPED_UNICODE));
            chmod($filepathMeta, 0644);
        }

        $existingData = json_decode(file_get_contents($filepathMeta), true, flags: JSON_THROW_ON_ERROR);
        $existingData[$resource] = $id;

        file_put_contents($filepathMeta, json_encode($existingData, JSON_UNESCAPED_UNICODE));
        chmod($filepathMeta, 0644);

        $this->lastInsertId = (string)$id;
    }

    /**
     * @throws \JsonException
     */
    private function loadLastInsertedId(string $resource): int
    {
        $filepathMeta = $this->aliasManager->buildPath('@file-resources/_meta.json');

        if (file_exists($filepathMeta) === false) {
            $this->saveLastInsertedId($resource, 0);
        }

        $existingData = json_decode(file_get_contents($filepathMeta), true, flags: JSON_THROW_ON_ERROR);

        return $existingData[$resource] ?? 0;
    }
}
