<?php

declare(strict_types=1);

namespace Monoelf\Framework\queue;

final readonly class Queue
{
    public function __construct(
        private RedisClient $redis,
        private string $name,
    ) {}

    public function push(JobInterface $job): void
    {
        $this->redis->push(
            $this->name,
            serialize($job)
        );
    }

    public function pop(): ?JobInterface
    {
        $payload = $this->redis->pop($this->name);

        if ($payload === null) {
            return null;
        }

        $job = unserialize($payload);

        if ($job instanceof JobInterface === true) {
            return $job;
        }

        return null;
    }
}
