<?php

declare(strict_types=1);

namespace App\Core;

class View
{
    private static array $shared = [];

    public static function share(array $data): void
    {
        self::$shared = array_replace(self::$shared, $data);
    }

    public static function render(
        string $page,
        array $data = [],
        string $layout = 'app'
    ): void {
        // YOUR ACTUAL VIEW FOLDER
        $viewPath = __DIR__ . '/../../resources/views/';

        // PAGE LOCATION
        $pageFile = $viewPath . 'pages/' . $page . '.php';

        // LAYOUT LOCATION
        $layoutFile = $viewPath . 'layouts/' . $layout . '.php';

        if (!is_file($pageFile)) {
            throw new \RuntimeException(
                "Page not found: " . $pageFile
            );
        }

        if (!is_file($layoutFile)) {
            throw new \RuntimeException(
                "Layout not found: " . $layoutFile
            );
        }

        // Extract data so variables are available in both page and layout
        extract(array_replace(self::$shared, $data), EXTR_SKIP);

        // Render page first (capture output)
        ob_start();
        require $pageFile;
        $pageContent = ob_get_clean();

        // Now render layout with page content and all extracted variables available
        require $layoutFile;
    }
}
