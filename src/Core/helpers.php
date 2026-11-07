<?php

/**
 * Global view helpers.
 */

if (!function_exists('asset')) {
    /**
     * Build a URL for a public asset that works whether the app is served:
     *  - from the document root            ( /assets/... )
     *  - from a sub-directory              ( /my-app/public/assets/... )
     *  - with the project root as docroot  ( /public/assets/... )
     *
     * Supports query strings, e.g. asset('/assets/css/user/main.css?v=1').
     */
    function asset(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        $base = '';

        // Sub-directory deployments (Apache: /subdir/index.php,
        // or /subdir/public/index.php).
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        if ($script !== '' && str_ends_with($script, '.php')) {
            // Windows PHP: dirname('/index.php') yields a single backslash,
            // so normalize it back — otherwise $base becomes '\' and every
            // asset URL renders as '\/assets/...' (parsed by browsers as a
            // protocol-relative '//assets/...' URL, which never loads).
            $dir = str_replace('\\', '/', rtrim(dirname($script), '/'));
            if ($dir !== '' && $dir !== '.' && $dir !== '/') {
                $base = $dir;
            }
        }

        // Project root (instead of public/) used as the document root.
        $docRoot     = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
        $publicRoot  = realpath(__DIR__ . '/../../public') ?: '';
        $projectRoot = realpath(__DIR__ . '/../..') ?: '';
        if (
            $docRoot !== '' && $publicRoot !== '' && $docRoot === $projectRoot
            && !str_ends_with($base, '/public')
        ) {
            $base .= '/public';
        }

        return $base . $path;
    }
}
