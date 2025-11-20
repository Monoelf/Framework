<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\response;

use Monoelf\Framework\http\response\Response;

final class CreateResponse extends Response
{
    public function __construct(?string $body = null)
    {
        parent::__construct(201, $body);
    }
}
