<?php

declare(strict_types=1);

namespace Monoelf\Framework\view;

interface ViewInterface
{
    /**
     * @param string $view
     * @param array $params
     * @return string
     * @throws ViewNotFoundException
     */
    function render(string $view, array $params = []): string;
}
