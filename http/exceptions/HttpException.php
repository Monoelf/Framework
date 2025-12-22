<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\exceptions;

use Monoelf\Framework\common\exceptions\BaseException;

class HttpException extends BaseException
{
    public function __construct(private readonly int $statusCode, mixed $message)
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
