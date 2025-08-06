<?php

declare(strict_types=1);

namespace Monoelf\Framework\logger;

final class DebugTagStorage implements DebugTagStorageInterface
{
    /**
     * Cтрока значения тега отладки
     *
     * @var string|null
     */
    private string $tag;

    /**
     * @param string|null $tag
     */
    public function __construct(private readonly DebugTagGenerator $debugTagGenerator)
    {
        $this->tag = $this->debugTagGenerator->getTag();
    }


    /**
     * Получить значение тега
     *
     * @return string
     */
    public function getTag(): string
    {
        if ($this->tag === null) {
            throw new \RuntimeException('Тег отладки не определен');
        }

        return $this->tag;
    }

    /**
     * Установить значение тега
     *
     * @param string $tag
     * @return void
     */
    public function setTag(string $tag): void
    {
        $this->tag = $tag;
    }
}
