<?php

declare(strict_types=1);

namespace Monoelf\Framework\logger;

final class DebugTagGenerator
{
    private string $tag;
    public function __construct(private readonly bool $isUpdatable = false)
    {
        $this->tag = $this->generateTag();
    }

    public function updateTag(): void
    {
        if ($this->isUpdatable === false) {
            throw new \RuntimeException('Обновление тега запрещено');
        }

        $this->tag = $this->generateTag();
    }

    private function generateTag(): string
    {
        return md5(sprintf(
            "%s-%d-%04x",
            date('YmdHisu'),
            getmypid(),
            random_int(0, 0xFFFF)
        ));
    }

    public function getTag(): string
    {
        return $this->tag;
    }
}
