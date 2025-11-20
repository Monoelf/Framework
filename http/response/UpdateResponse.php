<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\response;

use Monoelf\Framework\http\response\Response;

final class UpdateResponse extends Response
{
    public function __construct()
    {
        parent::__construct(204);
    }
}
