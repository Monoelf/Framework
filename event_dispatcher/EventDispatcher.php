<?php

declare(strict_types=1);

namespace Monoelf\Framework\event_dispatcher;

use Monoelf\Framework\container\ContainerInterface;

final class EventDispatcher implements EventDispatcherInterface
{
    public array $observers = [];

    public function __construct(private readonly ContainerInterface $container) {}

    public function configure(array $config): void
    {
        foreach ($config as $eventName => $observers) {
            foreach ($observers as $observer) {
                $this->attach($eventName, $observer);
            }
        }
    }

    /**
     * @param string $eventName
     * @param string|array|object $observer
     * @return void
     */
    public function attach(string $eventName, string|array|object $observer): void
    {
        $observerData = $this->parseObserver($observer);
        $key = $this->findObserver($eventName, $observerData);

        if ($key !== false) {
            return;
        }

        if (isset($this->observers[$eventName]) === false) {
            $this->observers[$eventName] = [];
        }

        $this->observers[$eventName][] = $observerData;
    }

    private function parseObserver(string|array|object $observer): array
    {
        if (is_string($observer) === true) {
            if ($this->isExistsClassOrInterface($observer) === false) {
                throw new \InvalidArgumentException("Класс/Интерфейс '{$observer}' не существует.");
            }

            return [$observer, 'handle'];
        }

        if (is_array($observer) === true) {
            if (count($observer) !== 2) {
                throw new \InvalidArgumentException(
                    "Не соответствует формату [string/object instance, string method]"
                );
            }

            if (
                is_object($observer[0]) === false
                && (is_string($observer[0]) === false || $this->isExistsClassOrInterface($observer[0]) === false)
            ) {
                throw new \InvalidArgumentException(
                    "Первый элемент должен быть объектом или именем класса/интерфкейса"
                );
            }

            if (is_string($observer[1]) === false) {
                throw new \InvalidArgumentException("Второй элемент должен быть строкой.");
            }

            return [$observer[0], $observer[1]];
        }

        if (is_callable($observer) === true) {
            return [$observer, '__invoke'];
        }

        if (is_object($observer) === true) {
            if (is_subclass_of($observer, ObserverInterface::class) === true) {
                return [$observer, 'handle'];
            }

            throw new \InvalidArgumentException("Объект не реализует ObserverInterface");
        }

        throw new \InvalidArgumentException("Неизвестный формат наблюдателя");
    }

    private function isExistsClassOrInterface(string $observer): bool
    {
        return class_exists($observer) === true || interface_exists($observer) === true;
    }

    public function detach(string $eventName, array|string|object $observer): void
    {
        $key = $this->findObserver($eventName, $this->parseObserver($observer));

        if ($key !== false) {
            unset($this->observers[$eventName][$key]);
        }
    }

    private function findObserver(string $eventName, array $observerData): int|false
    {
        if (isset($this->observers[$eventName]) === false) {
            return false;
        }

        return array_search($observerData, $this->observers[$eventName]);
    }

    public function trigger(string $eventName, ?Message $message = null): void
    {
        $message = $message ?? new Message();

        if (isset($this->observers[$eventName]) === true) {
            foreach ($this->observers[$eventName] as $observer) {
                $this->container->call($observer[0], $observer[1], ['message' => $message, 'eventName' => $eventName]);
            }
        }
    }

    public function getObservers(string $eventName): array
    {
        return isset($this->observers[$eventName]) === true ? $this->observers[$eventName] : [];
    }
}
