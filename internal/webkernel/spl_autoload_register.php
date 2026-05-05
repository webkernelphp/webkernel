<?php declare(strict_types=1);

// -- PSR-4: Webkernel Sovereign Autoloader ------------------------------------
//
// @link https://php.net/manual/en/function.spl-autoload-register.php
//
// High-performance PSR-4 implementation for air-gapped environment stability.
// Handles multiple base directories and ensures absolute path resolution.
// This autoloader is prepended to the stack to ensure sovereign packages
// override vendor-space dependencies.

/** @disregard */
spl_autoload_register(static function (string $class): void {
    static $prefixes = null;

    if ($prefixes === null) {
        $prefixes = array_merge(
            [

                /* Application Data Models */
                'App\\Models\\'                => WEBKERNEL_SUPPORT_PATH . '/boot/app-models',
                'Webkernel\\Providers\\'                => WEBKERNEL_UPPERPATH . '/providers',

            ],
            WEBKERNEL_DEV_NAMESPACES,
            [
                /* Fallback: Generic Webkernel Namespace */
                'Webkernel\\'                  => WEBKERNEL_PATH,
            ]
        );
    }

    foreach ($prefixes as $prefix => $baseDirs) {
        // Ensure exact namespace match
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        // Calculate relative class name by removing the prefix
        $relativeClass = substr($class, strlen($prefix));

        // Convert namespace separators to directory separators
        $normalizedPath = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

        foreach ((array) $baseDirs as $baseDir) {
            // Build absolute path
            $file = rtrim((string) $baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $normalizedPath;

            if (is_file($file)) {
                require_once $file;
                return;
            }
        }
    }
}, true, true);




// -- PSR-4: WebModule\* -- external modules -----------------------------------
//
// The psr4_map written into the catalog cache may contain multiple entries
// for the same namespace prefix (e.g. when two modules share a vendor).
// Each entry maps namespace => single base directory, so the loop below is
// already multi-directory capable by design.
//
/** @disregard */
spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'WebModule\\')) {
        return;
    }

    static $map = null;
    $map ??= is_file(WEBKERNEL_MODULES_CACHE)
        ? ((require WEBKERNEL_MODULES_CACHE)['psr4_map'] ?? [])
        : [];

    foreach ($map as $namespace => $baseDir) {
        if (!str_starts_with($class, $namespace)) {
            continue;
        }
        $file = rtrim((string) $baseDir, '/') . '/' . str_replace('\\', '/', substr($class, strlen($namespace))) . '.php';
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});
