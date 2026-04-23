<?php declare(strict_types=1);

/* Define Support Path */

defined('WEBKERNEL_MAIN_SUPPORT_PATH')
    || define('WEBKERNEL_MAIN_SUPPORT_PATH', WEBKERNEL_PATH . '/support');

/* --- Runtime & helpers --- */

defined('WEBKERNEL_HELPERS_ROOT')
    || define('WEBKERNEL_HELPERS_ROOT', WEBKERNEL_MAIN_SUPPORT_PATH . '/platform-helpers');

defined('WEBKERNEL_DIST_ROOT')
    || define('WEBKERNEL_DIST_ROOT', WEBKERNEL_MAIN_SUPPORT_PATH . '/dist');

defined('WEBKERNEL_STATIC_ROOT')
    || define('WEBKERNEL_STATIC_ROOT', WEBKERNEL_MAIN_SUPPORT_PATH . '/static');

defined('WEBKERNEL_ERRORS_PAGES_PATH')
    || define('WEBKERNEL_ERRORS_PAGES_PATH', WEBKERNEL_MAIN_SUPPORT_PATH . '/boot/_dist/error-pages');
