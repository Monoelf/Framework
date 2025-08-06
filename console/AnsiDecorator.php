<?php

declare(strict_types=1);

namespace Monoelf\Framework\console;

final class AnsiDecorator
{
    /**
     * Отформатировать строку в формате ANSI
     *
     * @param  string $message исходное сообщение
     * @param  array $format формат (цвет, стиль)
     * @return string
     */
    public function decorate(string $message, array $format = []): string
    {
        $code = implode(';', $format);

        return "\033[0m" . ($code !== '' ? "\033[" . $code . 'm' : '') . $message . "\033[0m";
    }
}
