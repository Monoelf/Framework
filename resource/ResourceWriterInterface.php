<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource;

interface ResourceWriterInterface
{
    public function setResourceName(string $name): static;

    public function create(array $values): int;

    public function update(string|int $id, array $values): int;

    public function patch(string|int $id, array $values): int;

    public function delete(string|int $id): int;
}
