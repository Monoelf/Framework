<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\dto;

use Monoelf\Framework\http\dto\BaseControllerResponse;

final class CreateResponse extends BaseControllerResponse
{
    public function __construct(?string $body = null)
    {
        parent::__construct(201, $body);
    }
}
