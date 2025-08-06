<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\exceptions;

class HttpException extends \Exception
{
    public function __construct(private readonly int $statusCode, string $message)
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
