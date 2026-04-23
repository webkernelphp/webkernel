<?php declare(strict_types=1);

// -- Foundation paths ---------------------------------------------------------
defined('BASE_PATH')      || define('BASE_PATH',      dirname(__DIR__, 2));
defined('WEBKERNEL_PATH') || define('WEBKERNEL_PATH', __DIR__);

// -- Release data -- stamped by Makefile release|patch|minor|major|info -------
defined('WEBKERNEL_VERSION')     || define('WEBKERNEL_VERSION','0.11.5');
defined('WEBKERNEL_BUILD')       || define('WEBKERNEL_BUILD',96);
defined('WEBKERNEL_SEMVER')      || define('WEBKERNEL_SEMVER','0.11.5+96');
defined('WEBKERNEL_CODENAME')    || define('WEBKERNEL_CODENAME','waterfall');
defined('WEBKERNEL_CHANNEL')     || define('WEBKERNEL_CHANNEL','stable');
defined('WEBKERNEL_RELEASED_AT') || define('WEBKERNEL_RELEASED_AT','2026-04-23');
defined('WEBKERNEL_BRANCH')      || define('WEBKERNEL_BRANCH','main');
defined('WEBKERNEL_TAG')         || define('WEBKERNEL_TAG','v0.11.5');

defined('WEBKERNEL_REQUIRES') || define('WEBKERNEL_REQUIRES', [
    'php'       => '8.4.19',
    'laravel'   => '13.4.0',
    'filament'  => '5.5.0',
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

// -- Constants ----------------------------------------------------------------
$_support_boot = WEBKERNEL_PATH . '/support/boot/';

require "{$_support_boot}boot-constants/010-paths.php";
require "{$_support_boot}boot-constants/020-registry.php";
require "{$_support_boot}boot-constants/030-runtime.php";
require "{$_support_boot}boot-constants/040-thresholds.php";
require "{$_support_boot}boot-constants/050-security.php";
require "{$_support_boot}boot-constants/060-globals.php";

/* Arcanes subsystem constants (must come before autoloaders) */
require "{$_support_boot}boot-constants/070-arcanes.php";

/* Dev mode + dev namespace map */
require "{$_support_boot}boot-actions/010-check-devmode.php";

/* Cache directory bootstrap */
require "{$_support_boot}boot-actions/020-cache-dir-bootstrap.php";

/* PSR-4: Webkernel packages */
require "{$_support_boot}boot-actions/030-autoload-platform.php";

/* PSR-4: WebModule\* -- external modules */
require "{$_support_boot}boot-actions/040-autoload-modules.php";

/* Boot services */
$_bs = "{$_support_boot}boot-services/";
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

/* First-boot guard (.env + SQLite) */
require "{$_support_boot}boot-actions/050-setup_env.php";

/* Platform helpers loader */
require "{$_support_boot}platform-actions/010-load-helpers.php";

// Cleanup
unset($_support_boot);

// -- Boot ---------------------------------------------------------------------
return \Webkernel\WebApp::configure(...)
    (basePath: BASE_PATH, version: WEBKERNEL_VERSION)
    ->create(...);
