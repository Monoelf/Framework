<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\dto;

use Monoelf\Framework\http\dto\BaseControllerResponse;

class JsonResponse extends BaseControllerResponse
{
    public function __construct(mixed $data = null, int $status = 200)
    {
        if ($data !== null) {
            $data = is_array($data) === false ? array($data) : $data;
        }

        parent::__construct(
            $status,
            $data,
            'application/json'
        );
    }
}
