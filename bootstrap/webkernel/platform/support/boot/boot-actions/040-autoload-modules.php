<?php declare(strict_types=1);

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
