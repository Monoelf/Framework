<?php

declare(strict_types=1);

namespace Monoelf\Framework\resource\connection;

use Monoelf\Framework\resource\connection\ConnectionFactoryInterface;

final class ConnectionFactory implements ConnectionFactoryInterface
{
    public function createConnection(array $config): DataBaseConnectionInterface
    {
        return match ($config['driver']) {
            'mysql' => new DataBaseConnection($config),
            'file' => new JsonDataBaseConnection(...$config),
        };
    }
}
