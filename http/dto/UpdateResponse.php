<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\dto;

use Monoelf\Framework\http\dto\BaseControllerResponse;

final class UpdateResponse extends BaseControllerResponse
{
    public function __construct()
    {
        parent::__construct(204);
    }
}
