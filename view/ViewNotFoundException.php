<?php

declare(strict_types=1);

namespace Monoelf\Framework\view;

use Exception;

class ViewNotFoundException extends Exception 
{
    public function __construct(string $viewName)
    {
        parent::__construct("Представление {$viewName} не найдено");
    }
}
