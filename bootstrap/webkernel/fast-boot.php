<?php declare(strict_types=1);

// -- Foundation paths ---------------------------------------------------------
defined('BASE_PATH')      || define('BASE_PATH',      dirname(__DIR__, 2));
defined('WEBKERNEL_PATH') || define('WEBKERNEL_PATH', __DIR__);

// -- Release data -- stamped by Makefile release|patch|minor|major|info -------
defined('WEBKERNEL_VERSION')     || define('WEBKERNEL_VERSION',     '1.9.0');
defined('WEBKERNEL_BUILD')       || define('WEBKERNEL_BUILD',       1);
defined('WEBKERNEL_SEMVER')      || define('WEBKERNEL_SEMVER',      '1.9.0+1');
defined('WEBKERNEL_CODENAME')    || define('WEBKERNEL_CODENAME',    'waterfall');
defined('WEBKERNEL_CHANNEL')     || define('WEBKERNEL_CHANNEL',     'stable');
defined('WEBKERNEL_RELEASED_AT') || define('WEBKERNEL_RELEASED_AT', '2026-04-13');
defined('WEBKERNEL_COMMIT')      || define('WEBKERNEL_COMMIT',      'unknown');
defined('WEBKERNEL_COMMIT_FULL') || define('WEBKERNEL_COMMIT_FULL', 'unknown');
defined('WEBKERNEL_BRANCH')      || define('WEBKERNEL_BRANCH',      'main');
defined('WEBKERNEL_TAG')         || define('WEBKERNEL_TAG',         'v1.9.0');

defined('WEBKERNEL_REQUIRES') || define('WEBKERNEL_REQUIRES', [
    'php'      => '8.4.19',
    'laravel'  => '13.4.0',
    'filament' => '5.5.0',
    'livewire' => '4.2.4',
    'composer' => '2.9.5',
]);

defined('WEBKERNEL_COMPATIBLE_WITH') || define('WEBKERNEL_COMPATIBLE_WITH', [
    'php'      => '8.3.0',
    'laravel'  => '13.0.0',
    'filament' => '5.0.0',
    'livewire' => '1.0.0',
    'composer' => '2.5.0',
]);

// -- Constants ----------------------------------------------------------------
$_support = WEBKERNEL_PATH . '/support/';

require $_support . 'boot-constants/paths.php';
require $_support . 'boot-constants/registry.php';
require $_support . 'boot-constants/runtime.php';
require $_support . 'boot-constants/thresholds.php';
require $_support . 'boot-constants/security.php';
require $_support . 'boot-constants/globals.php';
require $_support . 'boot-constants/arcanes.php';   // Arcanes subsystem constants (must come before autoloaders)

// -- Dev mode + dev namespace map ---------------------------------------------
require $_support . 'boot-actions/devmode.php';

// -- Cache directory bootstrap ------------------------------------------------
is_dir(WEBKERNEL_CACHE_PATH) || @mkdir(WEBKERNEL_CACHE_PATH, 0750, true);

// -- PSR-4: Webkernel packages ------------------------------------------------
require $_support . 'boot-actions/autoload-platform.php';

// -- PSR-4: WebModule\* -- external modules -----------------------------------
require $_support . 'boot-actions/autoload-modules.php';

// -- Boot services ------------------------------------------------------------
$_bs = $_support . 'boot-services/';
require_once $_bs . '010-hmac-signer.php';
require_once $_bs . '020-webkernel-session.php';
require_once $_bs . '030-webkernel-router.php';
require_once $_bs . '040-branding.php';             // needs WebkernelRouter; defines WEBKERNEL_BRAND_LOGO_*
require_once $_bs . '050-emergency-page-builder.php';
require_once $_bs . '060-server-side-validator.php';
require_once $_bs . '070-http-client.php';
require_once $_bs . '080-setup-flow.php';           // needs WEBKERNEL_BRAND_LOGO_*
require_once $_bs . '090-global-helpers.php';
unset($_bs);

// -- First-boot guard (.env + SQLite) -----------------------------------------
require $_support . 'setup_env.php';

// Cleanup
unset($_support);

// -- Boot ---------------------------------------------------------------------
return fn (): \Webkernel\WebApp =>
    \Webkernel\WebApp::configure(basePath: BASE_PATH, version: WEBKERNEL_VERSION)->create();
