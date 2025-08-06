<?php

declare(strict_types=1);

namespace Monoelf\Framework\http;

use Psr\Http\Message\ServerRequestInterface;

interface HTTPKernelInterface
{
    /**
     * Обработка входящего запроса
     *
     * @return ServerResponseInterface объект ответа
     */
    public function handle(ServerRequestInterface $request): ServerResponseInterface;
}
