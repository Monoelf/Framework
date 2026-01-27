<?php

declare(strict_types=1);

namespace Monoelf\Framework\queue;

use Monoelf\Framework\container\ContainerInterface;

interface JobInterface
{
    public function doJob(ContainerInterface $container): void;
}
