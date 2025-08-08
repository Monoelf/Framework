<?php

declare(strict_types=1);

namespace Monoelf\Framework\common;

use Monoelf\Framework\config_storage\ConfigurationStorage;

final class AliasManager
{
    private array $aliases = [];
    private array $requiredAliases = [
        'framework',
        'app',
        'modules'
    ];

    public function __construct(ConfigurationStorage $configurationStorage)
    {
        $this->aliases = $configurationStorage->get('aliases');

        foreach ($this->requiredAliases as $requiredAlias) {
            $requiredAlias = '@' . $requiredAlias;

            if (isset($this->aliases[$requiredAlias]) === false) {
                throw new \InvalidArgumentException("Обязательный алиас '{$requiredAlias}' не задан");
            }
        }
    }

    public function addAlias(string $alias, string $path): void
    {
        $this->aliases[$alias] = $this->hasAlias($path) === true ? $this->buildPath($path) : $path;
    }

    public function buildPath(string $path): string
    {
        if (str_starts_with($path, '@') === false) {
            throw new \InvalidArgumentException('Алиас не указан');
        }

        $alias = substr($path, 0, strpos($path, '/'));

        if (isset($this->aliases[$alias]) === false || is_string($this->aliases[$alias]) === false) {
            throw new \InvalidArgumentException("Алиас '$alias' не задан или задан некорректно");
        }

        $builtPath = str_replace($alias, $this->aliases[$alias], $path);
        $builtPath = str_replace('//', '/', $builtPath);

        return $builtPath;
    }

    public function hasAlias(string $path): bool
    {
        return str_starts_with($path, '@') === true;
    }
}
