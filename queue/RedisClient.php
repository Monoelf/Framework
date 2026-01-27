<?php

declare(strict_types=1);

namespace Monoelf\Framework\queue;

use Redis;

final class RedisClient
{
    private Redis $redis;

    public function __construct(string $host, int $port)
    {
        $this->redis = new Redis();
        $this->redis->connect($host, $port);
    }

    public function set(string $key, string $value, int $expire): void
    {
        $this->redis->setex($key, $expire, $value);
    }

    public function get(string $key): ?string
    {
        $value = $this->redis->get($key);

        if ($value === false) {
            return null;
        }

        return $value;
    }

    public function delete(string $key): void
    {
        $this->redis->del($key);
    }

    /* ===== QUEUE ===== */

    public function push(string $queue, string $payload): void
    {
        $this->redis->rPush($queue, $payload);
    }

    public function pop(string $queue): ?string
    {
        $data = $this->redis->blPop($queue, 1);

        if (empty($data) === true) {
            return null;
        }

        return $data[1];
    }
}
