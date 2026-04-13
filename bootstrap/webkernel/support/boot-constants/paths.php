<?php declare(strict_types=1);
/* --- Cache paths ------*------*------ */
defined('WEBKERNEL_CACHE_PATH')            || define('WEBKERNEL_CACHE_PATH',            BASE_PATH . '/storage/webkernel/cache');
defined('WEBKERNEL_CACHE_PATH_MANIFEST')   || define('WEBKERNEL_CACHE_PATH_MANIFEST',   WEBKERNEL_CACHE_PATH . '/core.manifest.php');
defined('WEBKERNEL_MODULES_CACHE')         || define('WEBKERNEL_MODULES_CACHE',         WEBKERNEL_CACHE_PATH . '/modules.php');
defined('WEBKERNEL_MODULES_LOCK')          || define('WEBKERNEL_MODULES_LOCK',          WEBKERNEL_CACHE_PATH . '/.modules.lock');
defined('WEBKERNEL_INSTANCE_FILE')         || define('WEBKERNEL_INSTANCE_FILE',         BASE_PATH  . '/storage/webkernel/instance.json');
defined('WEBKERNEL_PHP_RELEASES_CACHE')    || define('WEBKERNEL_PHP_RELEASES_CACHE',    WEBKERNEL_CACHE_PATH . '/php-releases.json');
/* --- Modules root ------*------*------ */
defined('WEBKERNEL_MODULES_ROOT')          || define('WEBKERNEL_MODULES_ROOT',          BASE_PATH  . '/modules');
/* --- Helpers & runtime roots ------*------*------ */
defined('WEBKERNEL_HELPERS_ROOT')          || define('WEBKERNEL_HELPERS_ROOT',          WEBKERNEL_PATH . '/support/helpers');
defined('WEBKERNEL_DIST_ROOT')             || define('WEBKERNEL_DIST_ROOT',             WEBKERNEL_PATH . '/runtime/dist');
defined('WEBKERNEL_STATIC_ROOT')           || define('WEBKERNEL_STATIC_ROOT',           WEBKERNEL_PATH . '/runtime/static');
/* --- Platform capability roots ------*------*------ */
defined('WEBKERNEL_PLATFORM_ROOT')         || define('WEBKERNEL_PLATFORM_ROOT',         WEBKERNEL_PATH . '/platform');
defined('WEBKERNEL_ARCANES_ROOT')          || define('WEBKERNEL_ARCANES_ROOT',          WEBKERNEL_PATH . '/platform/arcanes');
/*
 * WEBKERNEL_PLATFORM_LOCATIONS — ordered list of directories that Arcanes\Modules
 * will scan for internal platform capabilities.
 *
 * Each entry is an absolute path whose immediate sub-directories are expected to
 * contain a platform.php manifest file.  Add new roots here; the discovery loop
 * in Arcanes\Modules::discoverPlatform() will pick them up automatically without
 * any further code changes.
 *
 * Convention: every platform capability lives directly under Webkernel\<Folder>
 * (e.g. bootstrap/webkernel/platform/panels  -> Webkernel\Panels).
 * No intermediate "Aptitudes" or similar wrapper namespace is used.
 */
defined('WEBKERNEL_PLATFORM_LOCATIONS') || define('WEBKERNEL_PLATFORM_LOCATIONS', [
    WEBKERNEL_PATH . '/aptitudes',   // panels, plugins, processes, ...
    WEBKERNEL_PATH . '/platform',    // system_panel, app-models, ...
]);
/* --- Installation sentinel & routing ------*------*------ */
defined('WEBKERNEL_DEPLOYMENT_FILE')       || define('WEBKERNEL_DEPLOYMENT_FILE',       BASE_PATH  . '/deployment.php');
defined('WEBKERNEL_HTTP_ROOT')             || define('WEBKERNEL_HTTP_ROOT',             '/');
defined('WEBKERNEL_INSTALLER_PATH_PREFIX') || define('WEBKERNEL_INSTALLER_PATH_PREFIX', 'installer');
defined('WEBKERNEL_INSTALLER_URL')         || define('WEBKERNEL_INSTALLER_URL',         WEBKERNEL_HTTP_ROOT . WEBKERNEL_INSTALLER_PATH_PREFIX);
defined('WEBKERNEL_HEALTH_PATH')           || define('WEBKERNEL_HEALTH_PATH',           'up');
/* --- SVG collections ------*------*------ */
defined('SVG_COLLECTION_PATHS') || define('SVG_COLLECTION_PATHS', [
    'bootstrap/webkernel/runtime/dist/export-svg/custom',
    'bootstrap/webkernel/runtime/dist/export-svg/lucide',
    'bootstrap/webkernel/runtime/dist/export-svg/simple-icons',
]);
