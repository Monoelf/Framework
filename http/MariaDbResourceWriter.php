<?php

declare(strict_types=1);

namespace Monoelf\Framework\http;

use Monoelf\Framework\resource\connection\DataBaseConnectionInterface;
use Monoelf\Framework\resource\ResourceWriterInterface;

class MariaDbResourceWriter implements ResourceWriterInterface
{
    private DataBaseConnectionInterface $connection;
    private string $resourceName = '';

    /**
     * @param DataBaseConnectionInterface $connection
     */
    public function __construct(DataBaseConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setResourceName(string $name): static
    {
        $this->resourceName = $name;

        return $this;
    }

    /**
     * @param array $values
     * @return int
     */
    public function create(array $values): int
    {
        return $this->connection->insert($this->resourceName, $values);
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
