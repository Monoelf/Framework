<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\response;

class Response
{
    public function __construct(
        public int $statusCode = 200,
        public ?string $responseBody = null,
        public string $contentType = 'text/html; charset=utf-8',
    ) {}
}
