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
    public function __construct(private readonly ConfigurationStorage $configurationStorage) {}

    /**
     * @throws HttpUnauthorizedException
     */
    public function __invoke(ServerRequestInterface $request, ServerResponseInterface $response, callable $next): void
    {
        $apiKey = $request->getHeaderLine('X-API-KEY');

        if ($apiKey === '') {
            throw new HttpUnauthorizedException('X-API-KEY отсутствует в заголовке запроса');
        }

        $validApiKey = $this->configurationStorage->getOrDefault('API_AUTH_KEY');

        if ($validApiKey === null) {
            throw new \RuntimeException('Ключ X-API-KEY не настроен на сервере');
        }

        if ($validApiKey !== $apiKey) {
            throw new HttpUnauthorizedException('Неверный X-API-KEY');
        }

        $next($request, $response);
    }
}
