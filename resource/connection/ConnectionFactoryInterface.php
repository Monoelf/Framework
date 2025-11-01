<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource\connection;

interface ConnectionFactoryInterface
{
    public function createConnection(array $config): DataBaseConnectionInterface;
}
