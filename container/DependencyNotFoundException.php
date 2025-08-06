<?php

declare(strict_types=1);

namespace Monoelf\Framework\container;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

class DependencyNotFoundException extends Exception implements NotFoundExceptionInterface
{
    public function __construct(string $dependency){
        parent::__construct("Не удалось разрешить зависимость {$dependency}");
    }
}
