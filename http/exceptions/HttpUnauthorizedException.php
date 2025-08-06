<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\exceptions;

use Monoelf\Framework\http\StatusCodeEnum;

final class HttpUnauthorizedException extends HttpException
{
    public function __construct(?string $message = null)
    {
        parent::__construct(
            StatusCodeEnum::STATUS_UNAUTHORIZED->value,
            $message ?? StatusCodeEnum::STATUS_UNAUTHORIZED->reasonPhrase()
        );
    }
}
