<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\exceptions;

use Monoelf\Framework\http\StatusCodeEnum;

final class HttpBadRequestException extends HttpException
{
    public function __construct(?string $message = null)
    {
        parent::__construct(
            StatusCodeEnum::STATUS_BAD_REQUEST->value,
            $message ?? StatusCodeEnum::STATUS_BAD_REQUEST->reasonPhrase()
        );
    }
}
