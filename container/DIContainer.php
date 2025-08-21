<?php

declare(strict_types=1);

namespace Monoelf\Framework\container;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

final class DIContainer implements ContainerInterface
{
    private static ?self $instance = null;
    private array $singletons = [];

    protected function __construct(private array $config = []) {}

    /**
     * Запрещает клонирование объекта, являющегося синглтоном
     *
     * @throws \LogicException
     */
    public function __clone(): void
    {
        throw new \LogicException('Клонирование запрещено');
    }

    /**
     * Именованный конструктор
     * Создает экземпляр класса DIContainer
     *
     * @param array $config Массив конфигурации
     * @return DIContainer экземпляр класса DIContainer
     */
    public static function create(array $config = []): self
    {
        if (self::$instance !== null) {
            throw new \LogicException('Запрещено повторное создание');
        }

        self::$instance = new self($config);

        return self::$instance;
    }

    public function build(string $dependencyName, array $args = []): object
    {
        if ($dependencyName === ContainerInterface::class || $dependencyName === self::class) {
            return $this;
        }

        $dependency = $dependencyName;

        if ($this->has($dependencyName) === true) {
            $dependency = $this->config['definitions'][$dependencyName] ?? $this->config['singletons'][$dependencyName];
        }

        if (is_callable($dependency) === true) {
            return $this->call($dependency, '__invoke');
        }

        $reflection = new \ReflectionClass($dependency);

        if ($reflection->hasMethod('__construct') === false) {
            return $reflection->newInstance();
        }

        $parameters = $reflection->getConstructor()->getParameters();
        $dependencies = $this->resolveParameterDependencies($parameters, $args);

        return $reflection->newInstanceArgs($dependencies);
    }

    public function call(object|string $handler, string $method, array $args = []): mixed
    {
        $object = (is_string($handler) === true) ? $this->get($handler) : $handler;
        $reflection = new \ReflectionMethod($object, $method);
        $dependencies = $this->resolveParameterDependencies($reflection->getParameters(), $args);

        return $reflection->invokeArgs($object, $dependencies);
    }

    public function get(string $id): object
    {
        if ($id === ContainerInterface::class) {
            return $this;
        }

        if (array_key_exists($id, $this->config['singletons']) === true) {
            if (array_key_exists($id, $this->singletons) === false) {
                $this->singletons[$id] = $this->build($id);
            }

            return $this->singletons[$id];
        }

        if (array_key_exists($id, $this->config['definitions']) === false && class_exists($id) === false) {
            throw new DependencyNotFoundException($id);
        }

        return $this->build($id);
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->config['singletons']) === true
            || array_key_exists($id, $this->config['definitions']) === true;
    }

    /**
     * Разрешение зависимостей параметров (аргументов) метода
     *
     * @param array $parameters параметры метода
     * @param array $args заданные значения параметров
     * @return array подготовленные аргументы для вызова метода
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function resolveParameterDependencies(array $parameters, array $args): array
    {
        $dependencies = [];

        foreach ($parameters as $param) {
            $name = $param->getName();
            $type = $param->getType();

            if (array_key_exists($name, $args) === true) {
                $dependencies[] = $this->tryCastingType($type, $args[$name]);

                continue;
            }

            if ($param->isDefaultValueAvailable() === true) {
                $dependencies[] = $param->getDefaultValue();

                continue;
            }

            if ($type !== null && $type->isBuiltin() === false) {
                $dependencies[] = $this->get($type->getName());
            }
        }

        return $dependencies;
    }

    private function tryCastingType(\ReflectionNamedType|null $type, mixed $value): mixed
    {
        if (
            $type === null
            || $type->isBuiltin() === false
            || $type->getName() === gettype($value)
            || $type->getName() === 'callable'
        ) {
            return $value;
        }

        settype($value, $type->getName());

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function registerSingleton(string $dependencyName, string|callable $dependency): void
    {
        $this->config['singletons'][$dependencyName] = $dependency;
        unset($this->singletons[$dependencyName]);
    }
}
