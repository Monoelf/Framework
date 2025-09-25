<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource;

use Monoelf\Framework\resource\ResourceWriterInterface;

final class FileResourceWriter implements ResourceWriterInterface
{
    private ?string $resourceName = null;

    public function setResourceName(string $name): static
    {
        $this->resourceName = $name;

        return $this;
    }

    public function create(array $values): int
    {
        // TODO: кол-во обновленных строк?
    }

    public function update(int|string $id, array $values): int
    {
        // TODO: кол-во обновленных строк?
    }

    public function patch(int|string $id, array $values): int
    {
        // TODO: кол-во обновленных строк?
    }

    public function delete(int|string $id): int
    {
        // TODO: Implement delete() method.
    }
}
