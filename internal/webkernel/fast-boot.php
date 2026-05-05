<?php declare(strict_types=1);

// -- Foundation paths ---------------------------------------------------------
defined('BASE_PATH')              || define('BASE_PATH',              dirname(__DIR__, 2));
defined('WEBKERNEL_UPPERPATH')    || define('WEBKERNEL_UPPERPATH',    __DIR__);
defined('WEBKERNEL_PATH')         || define('WEBKERNEL_PATH',         WEBKERNEL_UPPERPATH . '/codebase');
defined('WEBKERNEL_SUPPORT_PATH') || define('WEBKERNEL_SUPPORT_PATH', WEBKERNEL_UPPERPATH . '/support');
defined('COMPOSER_VENDOR_PATH')   || define('COMPOSER_VENDOR_PATH',   BASE_PATH . '/platform/packages');

// -- Release Metadata ---------------------------------------------------------
defined('WEBKERNEL_VERSION')      || define('WEBKERNEL_VERSION',      '0.11.3');
defined('WEBKERNEL_BUILD')        || define('WEBKERNEL_BUILD',        131);
defined('WEBKERNEL_SEMVER')       || define('WEBKERNEL_SEMVER',       '0.11.3+131');
defined('WEBKERNEL_CODENAME')     || define('WEBKERNEL_CODENAME',     'waterfall');
defined('WEBKERNEL_CHANNEL')      || define('WEBKERNEL_CHANNEL',      'stable');
defined('WEBKERNEL_RELEASED_AT')  || define('WEBKERNEL_RELEASED_AT',  '2026-04-23');
defined('WEBKERNEL_BRANCH')       || define('WEBKERNEL_BRANCH',       'main');
defined('WEBKERNEL_TAG')          || define('WEBKERNEL_TAG',          'v0.11.3');

// -- Release Requirements -----------------------------------------------------
defined('WEBKERNEL_REQUIRES') || define('WEBKERNEL_REQUIRES', [
    'php'       => '8.4.19',
    'laravel'   => '13.6.0',
    'filament'  => '5.6.0',
    'livewire'  => '4.2.4',
    'composer'  => '2.9.5',
]);

defined('WEBKERNEL_COMPATIBLE_WITH') || define('WEBKERNEL_COMPATIBLE_WITH', [
    'php'       => '8.3.0',
    'laravel'   => '13.0.0',
    'filament'  => '5.0.0',
    'livewire'  => '1.0.0',
    'composer'  => '2.5.0',
]);

// -- Ensure Framework's PackageManifest finds the renamed vendor directory -----
putenv('COMPOSER_VENDOR_DIR=' . COMPOSER_VENDOR_PATH);

// -- Load Master Constants File ------------------------------------------------
require WEBKERNEL_UPPERPATH . '/instance-constants.php';

// -- Dev Mode Detection -------------------------------------------------------
(static function (): void {
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

// -- Cache Directory Bootstrap -------------------------------------------------
is_dir(WEBKERNEL_CACHE_PATH) || @mkdir(WEBKERNEL_CACHE_PATH, 0750, true);

// -- PSR-4 Autoloaders ---------------------------------------------------------
require WEBKERNEL_UPPERPATH . '/spl_autoload_register.php';

// -- Pre-Boot Services ---------------------------------------------------------
$_support = WEBKERNEL_SUPPORT_PATH;

/* Boot services */
$_bs = "{$_support}/boot/services/";
require_once "{$_bs}010-hmac-signer.php";
require_once "{$_bs}020-webkernel-session.php";
require_once "{$_bs}030-webkernel-router.php";

/* Branding needs WebkernelRouter; defines WEBKERNEL_BRAND_LOGO_* */
require_once "{$_bs}040-branding.php";
require_once "{$_bs}050-emergency-page-builder.php";
require_once "{$_bs}060-server-side-validator.php";
require_once "{$_bs}070-http-client.php";

/* needs WEBKERNEL_BRAND_LOGO_* */
require_once "{$_bs}080-setup-flow.php";
require_once "{$_bs}090-global-helpers.php";
unset($_bs);

// -- Environment & Database Bootstrap -----------------------------------------
require "{$_support}/boot/actions/050-setup_env.php";

// -- Platform Helpers Loader --------------------------------------------------
require "{$_support}/boot/actions/060-load-helpers.php";

// Cleanup
unset($_support);

// -- Boot WebApp --------------------------------------------------------------
return \Webkernel\WebApp::configure(...)
    (basePath: BASE_PATH, version: WEBKERNEL_VERSION)
    ->create(...);
