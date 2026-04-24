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
                /* Commands and Providers */
                'Webkernel\\Commands\\'            => WEBKERNEL_PATH . '/platform/commands',

                /* Application Data Models */
                'App\\Models\\'              => WEBKERNEL_PATH . '/support/boot/app-models',

            ],
            WEBKERNEL_DEV_NAMESPACES,
            [
                /* Fallback: Generic Webkernel Namespace */
                'Webkernel\\'           => WEBKERNEL_PATH . '/src',
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
