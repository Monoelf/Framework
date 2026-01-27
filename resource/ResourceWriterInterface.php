<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource;

interface ResourceWriterInterface
{
    public function setResourceName(string $name): static;

    public function setAccessibleFields(array $fieldNames): static;

    public function setRelationships(array $relationships): static;

    public function createWithRelated(array $values, array $relationships): ?string;

    public function create(array $values): ?string;

    public function update(string|int $id, array $values): int;

    public function patch(string|int $id, array $values): int;

    public function delete(string|int $id): int;
}
