<?php declare(strict_types=1);

/**
 * Loads all branding sources from all registered brand folders.
 */

require_once __DIR__ . '/branding_registry.php';

$basePath = dirname(__DIR__) . '/boot-branding';

$brands = [
    'webkernel',
    'numerimondes',
    'thebestrecruit',
];

foreach ($brands as $brand) {
    $path = $basePath . '/' . $brand;

    if (! is_dir($path)) {
        continue;
    }

    foreach (glob($path . '/*.php') as $file) {
        require $file;
    }
}

/**
 * Final step: freeze routes
 */
webkernelRegisterBrandingRoutes();
