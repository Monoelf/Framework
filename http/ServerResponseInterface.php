<?php

declare(strict_types=1);

namespace Monoelf\Framework\http;

use Psr\Http\Message\ResponseInterface;

interface ServerResponseInterface extends ResponseInterface
{
    public function send(): void;
}
