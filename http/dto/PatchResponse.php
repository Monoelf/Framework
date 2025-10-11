<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\dto;

use Monoelf\Framework\http\dto\ResponseDto;

final class PatchResponse extends ResponseDto
{
    public function __construct()
    {
        parent::__construct(204);
    }
}
