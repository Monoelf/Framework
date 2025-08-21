<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\router\middlewares;

use Monoelf\Framework\common\ErrorHandlerInterface;
use Monoelf\Framework\common\StrategyNameEnum;
use Monoelf\Framework\http\exceptions\HttpUnauthorizedException;
use Monoelf\Framework\http\router\MiddlewareInterface;
use Monoelf\Framework\http\ServerResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class JsonErrorHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ErrorHandlerInterface $errorHandler) {}

    /**
     * @throws HttpUnauthorizedException
     */
    public function __invoke(ServerRequestInterface $request, ServerResponseInterface $response, callable $next): void
    {
        $this->errorHandler->defineMode(StrategyNameEnum::JSON->value);

        $next($request, $response);
    }
}
