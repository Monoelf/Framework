<?php

declare(strict_types=1);

namespace Monoelf\Framework\console;

interface ConsoleKernelInterface
{
    /**
     * Возврат имени приложения
     *
     * @return string|null
     */
    public function getAppName(): ?string;

    /**
     * Возврат версии приложения
     *
     * @return string|null
     */
    public function getVersion(): ?string;

    /**
     * Возврат карты команд
     *
     * @return array
     */
    public function getCommands(): array;

    /**
     * Регистрация неймспейсов команд
     *
     * @param array $commandNamespaces
     * @return void
     */
    function registerCommandNamespaces(array $commandNamespaces): void;

    /**
     * Обработка запроса
     *
     * @return int
     */
    public function handle(): int;

    public function terminate(int $status): never;
}
