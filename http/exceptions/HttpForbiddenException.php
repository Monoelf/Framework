<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\exceptions;

use Monoelf\Framework\http\StatusCodeEnum;

final class HttpForbiddenException extends HttpException
{
    public function __construct(?string $message = null)
    {
        parent::__construct(
            StatusCodeEnum::STATUS_FORBIDDEN->value,
            $message ?? StatusCodeEnum::STATUS_FORBIDDEN->reasonPhrase()
        );
    }
}
