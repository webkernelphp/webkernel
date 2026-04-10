<?php declare(strict_types=1);

// ── Foundation paths ──────────────────────────────────────────────────────────
defined('BASE_PATH')      || define('BASE_PATH',      dirname(__DIR__, 2));
defined('WEBKERNEL_PATH') || define('WEBKERNEL_PATH', __DIR__);

// ── Release data — stamped by Makefile release|patch|minor|major|info ─────────
defined('WEBKERNEL_VERSION')       || define('WEBKERNEL_VERSION','1.8.1');
defined('WEBKERNEL_BUILD')         || define('WEBKERNEL_BUILD',63);
defined('WEBKERNEL_SEMVER')        || define('WEBKERNEL_SEMVER','1.8.1+63');
defined('WEBKERNEL_CODENAME')      || define('WEBKERNEL_CODENAME','waterfall');
defined('WEBKERNEL_CHANNEL')       || define('WEBKERNEL_CHANNEL','stable');
defined('WEBKERNEL_RELEASED_AT')   || define('WEBKERNEL_RELEASED_AT','2026-03-27');
defined('WEBKERNEL_COMMIT')        || define('WEBKERNEL_COMMIT','47d4300');
defined('WEBKERNEL_COMMIT_FULL')   || define('WEBKERNEL_COMMIT_FULL','47d43000ad00f68b43aa0389793f0b0142b68b95');
defined('WEBKERNEL_BRANCH')        || define('WEBKERNEL_BRANCH','main');
defined('WEBKERNEL_TAG')           || define('WEBKERNEL_TAG','1.8.0+62');

defined('WEBKERNEL_REQUIRES') || define('WEBKERNEL_REQUIRES', [
    'php'       => '8.4.16',
    'laravel'   => '13.1.1',
    'filament'  => '5.4.1',
    'livewire'  => '4.2.1',
    'composer'  => '2.9.5',
]);

defined('WEBKERNEL_COMPATIBLE_WITH') || define('WEBKERNEL_COMPATIBLE_WITH', [
    'php'       => '8.3.0',
    'laravel'   => '13.0.0',
    'filament'  => '5.0.0',
    'livewire'  => '1.0.0',
    'composer'  => '2.5.0',
]);

// ── Constants --------------───────────────────────────────────────────────────
$_wc = WEBKERNEL_PATH . '/config/';
require $_wc . 'constants/paths.php';
require $_wc . 'constants/registry.php';
require $_wc . 'constants/runtime.php';
require $_wc . 'constants/thresholds.php';
require $_wc . 'constants/security.php';
require $_wc . 'constants/globals.php';
unset($_wc);

// ── Dev mode + dev namespace map ──────────────────────────────────────────────
defined('IS_DEVMODE') || (static function (): void {
    $devFile = BASE_PATH . '/dev-tools.php';
    if (!is_file($devFile)) {
        define('IS_DEVMODE', false);
        define('WEBKERNEL_DEV_NAMESPACES', []);
        return;
    }
    $config = require $devFile;
    define('IS_DEVMODE', is_array($config) && ($config['dev-mode'] ?? false) === true);
    define('WEBKERNEL_DEV_NAMESPACES', IS_DEVMODE
        ? array_map(
            static fn(string $path): string => BASE_PATH . '/' . ltrim($path, '/'),
            (array) ($config['namespaces'] ?? [])
        )
        : []
    );
})();

// ── Cache directory bootstrap ─────────────────────────────────────────────────
is_dir(WEBKERNEL_CACHE_PATH) || @mkdir(WEBKERNEL_CACHE_PATH, 0750, true);

// ── PSR-4: Webkernel packages ─────────────────────────────────────────────────
/** @disregard */
spl_autoload_register(static function (string $class): void {
    static $prefixes = null;
    $prefixes ??= array_merge(
        [
            'App\\Models\\'          => WEBKERNEL_PATH . '/platform/app-models',
//            'Webkernel\\System\\'    => WEBKERNEL_PATH . '/platform/assessors/system',
            'Webkernel\\Arcanes\\'   => WEBKERNEL_PATH . '/platform/arcanes',
            'Webkernel\\Panel\\'     => WEBKERNEL_PATH . '/platform/panel',
            'Webkernel\\Pages\\'     => WEBKERNEL_PATH . '/platform/pages',
            'Webkernel\\Widgets\\'     => WEBKERNEL_PATH . '/platform/widgets',
            'Webkernel\\Aptitudes\\' => WEBKERNEL_PATH . '/platform/aptitudes',
        ],
        WEBKERNEL_DEV_NAMESPACES,
        [
            'Webkernel\\'            => WEBKERNEL_PATH . '/src',
        ]
    );
    foreach ($prefixes as $prefix => $baseDir) {
        if (!str_starts_with($class, $prefix)) { continue; }
        $file = $baseDir . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) { require $file; return; }
    }
});

// ── PSR-4: WebModule\* — external modules ────────────────────────────────────
/** @disregard */
spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'WebModule\\')) { return; }
    static $map = null;
    $map ??= is_file(WEBKERNEL_MODULES_CACHE)
        ? ((require WEBKERNEL_MODULES_CACHE)['psr4_map'] ?? [])
        : [];
    foreach ($map as $namespace => $baseDir) {
        if (!str_starts_with($class, $namespace)) { continue; }
        $file = $baseDir . '/' . str_replace('\\', '/', substr($class, strlen($namespace))) . '.php';
        if (is_file($file)) { require $file; return; }
    }
});

// ── Critical helper ───────────────────────────────────────────────────────────
require WEBKERNEL_HELPERS_ROOT . '/renderCriticalErrorHtml.php';

// ── First-boot guard (.env + SQLite) ─────────────────────────────────────────
require WEBKERNEL_PATH . '/config/setup_env.php';

// ── Boot ──────────────────────────────────────────────────────────────────────
return fn (): \Webkernel\WebApp =>
    \Webkernel\WebApp::configure(basePath: BASE_PATH, version: WEBKERNEL_VERSION)->create();
