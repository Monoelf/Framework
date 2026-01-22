<?php

declare(strict_types=1);

namespace Monoelf\Framework\queue;

interface JobInterface
{
    public function doJob(): void;
}
