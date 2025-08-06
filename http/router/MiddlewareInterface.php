<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\router;

use Monoelf\Framework\http\ServerResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface MiddlewareInterface
{
    /**
     * @param  ServerRequestInterface $request
     * @param  ServerResponseInterface $response
     * @param  callable $next
     * @return void
     */
    public function __invoke(ServerRequestInterface $request, ServerResponseInterface $response, callable $next): void;
}
