<?php

declare(strict_types=1);

namespace Monoelf\Framework\view;

use Monoelf\Framework\common\AliasManager;

class View implements ViewInterface
{
    public function __construct(
        private readonly AliasManager $aliasManager,
        string $rootPath,
    ) {
        $this->aliasManager->addAlias('@view', $rootPath);
    }

    public function render(string $view, array $params = []): string
    {
        if (str_starts_with($view, '@') === false) {
            $view = '@view/' . $view;
        }

        $filePath = $this->aliasManager->buildPath($view) . '.php';

        if (file_exists($filePath) === false) {
            throw new ViewNotFoundException($filePath);
        }

        extract($params);

        ob_start();

        include $filePath;

        return ob_get_clean();
    }
}
