<?php declare(strict_types=1);

/* --- Cache paths --- */

defined('WEBKERNEL_CACHE_PATH')
    || define('WEBKERNEL_CACHE_PATH', BASE_PATH . '/storage/webkernel/cache');

defined('WEBKERNEL_CACHE_PATH_MANIFEST')
    || define('WEBKERNEL_CACHE_PATH_MANIFEST', WEBKERNEL_CACHE_PATH . '/core.manifest.php');

defined('WEBKERNEL_MODULES_CACHE')
    || define('WEBKERNEL_MODULES_CACHE', WEBKERNEL_CACHE_PATH . '/modules.php');

defined('WEBKERNEL_MODULES_LOCK')
    || define('WEBKERNEL_MODULES_LOCK', WEBKERNEL_CACHE_PATH . '/.modules.lock');

defined('WEBKERNEL_INSTANCE_FILE')
    || define('WEBKERNEL_INSTANCE_FILE', BASE_PATH . '/storage/webkernel/instance.json');

defined('WEBKERNEL_PHP_RELEASES_CACHE')
    || define('WEBKERNEL_PHP_RELEASES_CACHE', WEBKERNEL_CACHE_PATH . '/php-releases.json');
