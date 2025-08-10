<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\router\middlewares;

use Monoelf\Framework\config_storage\ConfigurationStorage;
use Monoelf\Framework\http\exceptions\HttpUnauthorizedException;
use Monoelf\Framework\http\router\MiddlewareInterface;
use Monoelf\Framework\http\ServerResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class JsonResponseContentTypeMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ConfigurationStorage $configurationStorage) {}

    /**
     * @throws HttpUnauthorizedException
     */
    public function __invoke(ServerRequestInterface $request, ServerResponseInterface $response, callable $next): void
    {
        $response = $request->withHeader('Content-Type', 'application/json');

        $next($request, $response);
    }
}
