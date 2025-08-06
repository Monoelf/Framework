<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\exceptions;

Monoelf\Framework\http\StatusCodeEnum;

final class HttpNotAcceptableException extends HttpException
{
    public function __construct(?string $message = null)
    {
        parent::__construct(
            StatusCodeEnum::STATUS_NOT_ACCEPTABLE->value,
            $message ?? StatusCodeEnum::STATUS_NOT_ACCEPTABLE->reasonPhrase()
        );
    }
}
