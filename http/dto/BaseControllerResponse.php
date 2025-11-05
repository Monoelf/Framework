<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\dto;

class BaseControllerResponse
{
    public function __construct(
        public int $statusCode = 200,
        public mixed $responseBody = null,
        public ?string $contentType = null,
    ) {}
}
