<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\router\middlewares;

use Monoelf\Framework\config_storage\ConfigurationStorage;
use Monoelf\Framework\http\exceptions\HttpUnauthorizedException;
use Monoelf\Framework\http\router\MiddlewareInterface;
use Monoelf\Framework\http\ServerResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class XApiKeyMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly string $xApiKey,
    ) {}

    /**
     * @throws HttpUnauthorizedException
     */
    public function __invoke(ServerRequestInterface $request, ServerResponseInterface $response, callable $next): void
    {
        $xApiKey = $request->getHeaderLine('X-API-KEY');

        if ($xApiKey === '') {
            throw new HttpUnauthorizedException('X-API-KEY отсутствует в заголовке запроса');
        }

        if ($this->xApiKey !== $xApiKey) {
            throw new HttpUnauthorizedException('Неверный X-API-KEY');
        }

        $next($request, $response);
    }
}
