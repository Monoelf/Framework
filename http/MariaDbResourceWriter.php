<?php

declare(strict_types=1);

namespace Monoelf\Framework\http;

use InvalidArgumentException;
use Monoelf\Framework\http\exceptions\HttpBadRequestException;
use Monoelf\Framework\resource\connection\DataBaseConnectionInterface;
use Monoelf\Framework\resource\exceptions\DuplicateEntryException;
use Monoelf\Framework\resource\ResourceWriterInterface;

final class MariaDbResourceWriter implements ResourceWriterInterface
{
    private string $resourceName = '';

    /**
     * @param DataBaseConnectionInterface $connection
     */
    public function __construct(
        private readonly DataBaseConnectionInterface $connection
    ) {}

    /**
     * @param string $name
     * @return $this
     */
    public function setResourceName(string $name): static
    {
        if (empty($name) === true) {
            throw new InvalidArgumentException('Resource name cannot be empty');
        }

        $this->resourceName = $name;

        return $this;
    }

    /**
     * @param array $values
     * @return int
     */
    public function create(array $values): int
    {
        try {
            return $this->connection->insert($this->resourceName, $values);
        } catch (DuplicateEntryException $e) {
            throw new HttpBadRequestException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param string|int $id
     * @param array $values
     * @return int
     */
    public function update(string|int $id, array $values): int
    {
        return $this->connection->update(
            $this->resourceName,
            $values,
            ['id' => $id]
        );
    }

    /**
     * @param string|int $id
     * @param array $values
     * @return int
     */
    public function patch(string|int $id, array $values): int
    {
        return $this->update($id, $values);
    }

    /**
     * @param string|int $id
     * @return int
     */
    public function delete(string|int $id): int
    {
        return $this->connection->delete(
            $this->resourceName,
            ['id' => $id]
        );
    }
}
