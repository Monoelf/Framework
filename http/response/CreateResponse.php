<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\response;

use Monoelf\Framework\http\response\JsonResponse;

final class CreateResponse extends JsonResponse
{
    public function __construct(?string $body = null)
    {
        parent::__construct($body, 201);
    }
}
