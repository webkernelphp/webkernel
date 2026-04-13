<?php declare(strict_types=1);

// -- PSR-4: Webkernel packages ------------------------------------------------
//
// @link https://php.net/manual/en/function.spl-autoload-register.php
//
// Supports multiple base directories for the same namespace prefix.
// Map format:
//   'Prefix\\' => '/single/path'            -- single directory (string)
//   'Prefix\\' => ['/path/one', '/path/two'] -- multiple directories (array)
//
// The autoloader resolves the relative class file against each directory in
// order and requires the first match.  This mirrors the way Composer handles
// multiple PSR-4 roots for the same namespace.
//
/** @disregard */
spl_autoload_register(static function (string $class): void {
    static $prefixes = null;

    if ($prefixes === null) {
        $prefixes = array_merge(
            [
                'App\\Models\\'                      => WEBKERNEL_PATH . '/platform/app-models',
                'Webkernel\\Arcanes\\'               => WEBKERNEL_PATH . '/platform/arcanes',
                'Webkernel\\Panel\\'                 => WEBKERNEL_PATH . '/platform/panel',
                'Webkernel\\Pages\\'                 => WEBKERNEL_PATH . '/platform/pages',
                'Webkernel\\Widgets\\'               => WEBKERNEL_PATH . '/platform/widgets',
                'Webkernel\\Platform\\SystemPanel\\' => WEBKERNEL_PATH . '/platform/system_panel',
                //
                // Example of a namespace backed by multiple directories:
                //   'Webkernel\\Shared\\' => [
                //       WEBKERNEL_PATH . '/src/Shared',
                //       WEBKERNEL_PATH . '/platform/shared',
                //   ],
            ],
            WEBKERNEL_DEV_NAMESPACES,
            [
                // Catch-all: must come last so specific prefixes above win.
                'Webkernel\\' => WEBKERNEL_PATH . '/src',
            ]
        );
    }

    foreach ($prefixes as $prefix => $baseDirs) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $relative = str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

        foreach ((array) $baseDirs as $baseDir) {
            $file = rtrim((string) $baseDir, '/') . '/' . $relative;
            if (is_file($file)) {
                require $file;
                return;
            }
        }
    }
});
