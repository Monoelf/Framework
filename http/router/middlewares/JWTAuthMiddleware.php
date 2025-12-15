<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\router\middlewares;

use Monoelf\Framework\http\exceptions\HttpUnauthorizedException;
use Monoelf\Framework\http\jwt\JWTDecoder;
use Monoelf\Framework\http\router\MiddlewareInterface;
use Monoelf\Framework\http\ServerResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\ExpiredException;

final readonly class JWTAuthMiddleware implements MiddlewareInterface
{
    public function __construct(private JWTDecoder $decoder) {}

    public function __invoke(ServerRequestInterface $request, ServerResponseInterface $response, callable $next): void
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader) === true || stripos($authHeader, 'Bearer ') !== 0) {
            throw new HttpUnauthorizedException();
        }

        try {
            $decoded = $this->decoder->decode(substr($authHeader, 7));
        } catch (SignatureInvalidException|ExpiredException) {
            throw new HttpUnauthorizedException();
        }

        $request = $request->withAttribute('subject', $decoded['sub']);

        $next($request, $response);
    }
}
