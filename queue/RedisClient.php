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
        $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
    }

    public function set(string $key, mixed $value, int $expire): void
    {
        $this->redis->setex($key, $expire, $value);
    }

    public function get(string $key): mixed
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

    /* ===== MEMBERS ===== */

    public function addMember(string $key, string $member): void
    {
        $this->redis->sAdd($key, $member);
    }

    public function getMembers(string $key): array
    {
        return $this->redis->sMembers($key);
    }

    public function deleteMembers(string $key): void
    {
        $members = $this->getMembers($key);

        if (empty($members) === false) {
            $this->redis->del($members);
        }

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

    /* ===== PUBLISH/SUBSCRIBE ===== */

    public function publish(string $channel, string $data): void
    {
        $this->redis->publish($channel, $data);
    }

    public function subscribe(string $channel, callable $callback): void
    {
        $this->redis->subscribe([$channel], $callback);
    }
}
