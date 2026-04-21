<?php declare(strict_types=1);

/* --- Runtime & helpers --- */

defined('WEBKERNEL_HELPERS_ROOT')
    || define('WEBKERNEL_HELPERS_ROOT', WEBKERNEL_PATH . '/support/platform-helpers');

defined('WEBKERNEL_DIST_ROOT')
    || define('WEBKERNEL_DIST_ROOT', WEBKERNEL_PATH . '/runtime/dist');

defined('WEBKERNEL_STATIC_ROOT')
    || define('WEBKERNEL_STATIC_ROOT', WEBKERNEL_PATH . '/runtime/static');

defined('WEBKERNEL_ERRORS_PAGES_PATH')
    || define('WEBKERNEL_ERRORS_PAGES_PATH', WEBKERNEL_PATH . '/support/error-pages');
