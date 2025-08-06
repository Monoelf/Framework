<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\exceptions;

use Monoelf\Framework\http\StatusCodeEnum;

final class HttpNotFoundException extends HttpException
{
    public function __construct(?string $message = null)
    {
        parent::__construct(
            StatusCodeEnum::STATUS_NOT_FOUND->value,
            $message ?? StatusCodeEnum::STATUS_NOT_FOUND->reasonPhrase()
        );
    }
}
