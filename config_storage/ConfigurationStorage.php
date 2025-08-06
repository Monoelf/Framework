<?php

declare(strict_types=1);

namespace Monoelf\Framework\config_storage;

final class ConfigurationStorage
{
    private static ?self $instance = null;

    private function __construct(private array $config = [])
    {
        foreach (getenv() as $key => $value) {
            $this->config[$key] = $value;
        }
    }

    public static function create(array $config): self
    {
        if (self::$instance !== null) {
            throw new \LogicException('Запрещено повторное создание');
        }

        self::$instance = new self($config);

        return self::$instance;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __clone(): void
    {
        throw new \LogicException('Клонирование запрещено');
    }

    public function __wakeup(): void
    {
        throw new \LogicException('Десериализация запрещена');
    }

    public function get(string $key): mixed
    {
        if (isset($this->config[$key]) === false) {
            throw new \InvalidArgumentException("По ключу $key не найден параметр");
        }

        return $this->config[$key];
    }

    public function getOrDefault(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->config[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($this->config[$key]) === true;
    }
}
