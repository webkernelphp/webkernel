<?php declare(strict_types=1);
/**
 * Defines a constant if it is not already defined.
 * Optimized with global function escaping for direct Opcache resolution.
 *
 * @param string $name  The name of the constant.
 * @param mixed  $value The value of the constant.
 * @return void
 */
function defineIf(string $name, mixed $value): void
{
    if (!\defined($name)) {
        \define($name, $value);
    }
}
// -- Foundation paths ---------------------------------------------------------
defineIf('BASE_PATH',              dirname(__DIR__, 2));
defineIf('WEBKERNEL_UPPERPATH',    __DIR__);
defineIf('WEBKERNEL_PATH',         WEBKERNEL_UPPERPATH . '/codebase');
defineIf('WEBKERNEL_SUPPORT_PATH', WEBKERNEL_UPPERPATH . '/support');
defineIf('COMPOSER_VENDOR_PATH',   BASE_PATH . '/platform/packages');
// -- Release Metadata ---------------------------------------------------------
defineIf('WEBKERNEL_VERSION',     '0.11.3');
defineIf('WEBKERNEL_BUILD',       131);
defineIf('WEBKERNEL_SEMVER',      '0.11.3+131');
defineIf('WEBKERNEL_CODENAME',    'waterfall');
defineIf('WEBKERNEL_CHANNEL',     'stable');
defineIf('WEBKERNEL_RELEASED_AT', '2026-04-23');
defineIf('WEBKERNEL_BRANCH',      'main');
defineIf('WEBKERNEL_TAG',         'v0.11.3');
// -- Release Requirements -----------------------------------------------------
defineIf('WEBKERNEL_REQUIRES', [
    'php'       => '8.4.19',
    'laravel'   => '13.6.0',
    'filament'  => '5.6.0',
    'livewire'  => '4.2.4',
    'composer'  => '2.9.5',
]);
defineIf('WEBKERNEL_COMPATIBLE_WITH', [
    'php'       => '8.3.0',
    'laravel'   => '13.0.0',
    'filament'  => '5.0.0',
    'livewire'  => '1.0.0',
    'composer'  => '2.5.0',
]);
// -- Ensure Framework's PackageManifest finds the renamed vendor directory -----
putenv('COMPOSER_VENDOR_DIR=' . COMPOSER_VENDOR_PATH);
// -- Load Master Constants File ------------------------------------------------
require WEBKERNEL_UPPERPATH . '/standard-defines.php';
// -- Dev Mode Detection -------------------------------------------------------
(static function (): void {
    $devFile = DEVMODE_FILE;
    if (!is_file($devFile)) {
        \define('IS_DEVMODE', false);
        \define('WEBKERNEL_DEV_NAMESPACES', []);
        return;
    }
    $config = require $devFile;
    \define('IS_DEVMODE', is_array($config) && ($config['dev-mode'] ?? false) === true);
    \define('WEBKERNEL_DEV_NAMESPACES', IS_DEVMODE
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
