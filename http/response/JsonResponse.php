<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\response;

use Monoelf\Framework\http\response\Response;

class JsonResponse extends Response
{
    public function __construct(mixed $data = null, int $status = 200)
    {
        if ($data !== null) {
            $data = is_array($data) === false ? array($data) : $data;
        }

        parent::__construct(
            $status,
            json_encode($data),
            'application/json'
        );
    }
}
