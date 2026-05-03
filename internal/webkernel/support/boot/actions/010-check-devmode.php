<?php declare(strict_types=1);

// -- Dev mode + dev namespace map ---------------------------------------------
defined('IS_DEVMODE') || (static function (): void {
    $devFile = DEVMODE_FILE;
    if (!is_file($devFile)) {
        define('IS_DEVMODE', false);
        define('WEBKERNEL_DEV_NAMESPACES', []);
        return;
    }
    $config = require $devFile;
    define('IS_DEVMODE', is_array($config) && ($config['dev-mode'] ?? false) === true);
    define('WEBKERNEL_DEV_NAMESPACES', IS_DEVMODE
        ? array_map(
            static fn (string $path): string => BASE_PATH . '/' . ltrim($path, '/'),
            (array) ($config['namespaces'] ?? [])
        )
        : []
    );
})();
