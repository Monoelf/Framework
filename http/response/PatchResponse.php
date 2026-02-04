<?php

declare(strict_types=1);

namespace Monoelf\Framework\http\response;

use Monoelf\Framework\http\response\JsonResponse;

final class PatchResponse extends JsonResponse
{
    public function __construct()
    {
        parent::__construct(null, 204);
    }
}
