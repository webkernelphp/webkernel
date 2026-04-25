<?php

declare(strict_types=1);

// Load Webkernel fast-boot to register all custom autoloaders
require_once __DIR__ . '/../../fast-boot.php';

// Optionally load composer autoloader as fallback
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}
