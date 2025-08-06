<?php

declare(strict_types=1);

namespace Monoelf\Framework\logger;

abstract class AbstractLogger implements LoggerInterface
{
    /**
     * Логирование критической ошибки
     *
     * @param mixed $message сообщение
     * @return void
     */
    public function critical(mixed $message): void
    {
        $this->log(LogLevel::CRITICAL->value, $message);
    }

    /**
     * Логирование ошибки
     *
     * @param mixed $message сообщение
     * @return void
     */
    public function error(mixed $message): void
    {
        $this->log(LogLevel::ERROR->value, $message);
    }

    /**
     * Логирование предупредительного сообщения
     *
     * @param mixed $message сообщение
     * @return void
     */
    public function warning(mixed $message): void
    {
        $this->log(LogLevel::WARNING->value, $message);
    }

    /**
     * Логирование информационного сообщения
     *
     * @param mixed $message сообщение
     * @return void
     */
    public function info(mixed $message): void
    {
        $this->log(LogLevel::INFO->value, $message);
    }

    /**
     * Логирование сообщения отладки
     *
     * @param mixed $message сообщение
     * @return void
     */
    public function debug(mixed $message): void
    {
        $this->log(LogLevel::DEBUG->value, $message);
    }

    /**
     * Форматирование строки логирования
     *
     * @param string $level уровень логирования
     * @param mixed $message сообщение
     * @return string
     */
    abstract protected function formatMessage(string $level, mixed $message): string;

    /**
     * Запись форматированного лога в вывод
     *
     * @param string $log форматированный лог
     * @return void
     */
    abstract protected function writeLog(string $log): void;

    /**
     * Запись лога в вывод
     *
     * @param string $level уровень логирования
     * @param mixed $message сообщение
     * @return void
     */
    private function log(string $level, mixed $message): void
    {
        $log = $this->formatMessage($level, $message);
        $this->writeLog($log);
    }
}
