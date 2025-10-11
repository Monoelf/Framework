<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\dto;

use Monoelf\Framework\http\dto\ResponseDto;

class JsonResponse extends ResponseDto
{
    public function __construct(mixed $data = null, int $status = 200)
    {
        parent::__construct($status, array($data));
    }
}
