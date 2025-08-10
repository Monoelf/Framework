<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\router\middlewares;

use Monoelf\Framework\config_storage\ConfigurationStorage;
use Monoelf\Framework\container\ContainerInterface;
use Monoelf\Framework\http\exceptions\HttpUnauthorizedException;
use Monoelf\Framework\http\router\MiddlewareInterface;
use Monoelf\Framework\http\ServerResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class HTTPBasicMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly string $serverClientId,
        private readonly string $serverClientSecret,
    ) {}

    /**
     * @throws HttpUnauthorizedException
     */
    public function __invoke(ServerRequestInterface $request, ServerResponseInterface $response, callable $next): void
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if ($authHeader === '' || stripos($authHeader, 'Basic ') !== 0) {
            throw new HttpUnauthorizedException('Доступ запрещен! Авторизация не пройдена');
        }

        $credentials = base64_decode(substr($authHeader, 6));
        [$requestClientId, $requestClientSecret] = explode(':', $credentials, 2);

        if ($this->serverClientId !== $requestClientId || $this->serverClientSecret !== $requestClientSecret) {
            throw new HttpUnauthorizedException('Доступ запрещен! Авторизация не пройдена');
        }

        $next($request, $response);
    }
}
