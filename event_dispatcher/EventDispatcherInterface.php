<?php

declare(strict_types=1);

namespace Monoelf\Framework\event_dispatcher;

interface EventDispatcherInterface
{
    /**
     * Конфигурирует EventDispatcher с использованием предоставленного массива конфигурации
     *
     * @param array $config массив конфигурации,
     * где каждый элемент представляет собой массив вида
     * ['SOME_EVENT_NAME' => [BarObserver::class, BazObzerver::class]]
     * @return void
     */
    function configure(array $config): void;

    /**
     * Подписывает наблюдателя к определенному событию
     *
     * @param string $eventName имя события, к которому присоединяется наблюдатель
     * @param string $observer класс наблюдателя
     * @return void
     */
    function attach(string $eventName, string $observer): void;

    /**
     * Отписывает наблюдателя от определенного события
     *
     * @param string $eventName имя события, от которого отписывается наблюдатель
     * @param string|array|object $observer класс/объект наблюдателя, пара [класс/объект, метод] или колбек функция
     * @return void
     */
    function detach(string $eventName, string|array|object $observer): void;

    /**
     * Запускает событие и уведомляет соответствующего наблюдателя с переданным сообщением
     *
     * @param string $eventName Имя события, которое будет запущено
     * @param Message|null $message Сообщение, передаваемое наблюдателю
     * @return void
     */
    function trigger(string $eventName, ?Message $message = null): void;

    function getObservers(string $eventName): array;
}
