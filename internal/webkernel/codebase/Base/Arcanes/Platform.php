<?php declare(strict_types=1);

namespace Webkernel\Base\Arcanes;

use Webkernel\WebApp as Application;
use Illuminate\Support\Facades\Route;
use Livewire\Blaze\Blaze;
use Livewire\Livewire;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Core webkernel asset bootstrap.
 *
 * Owns exclusively the boot of assets that ship inside the webkernel package
 * itself (everything under WEBKERNEL_PATH).  It has zero knowledge of external
 * modules or the artifact catalog -- that is the sole responsibility of
 * Webkernel\Base\Arcanes\Modules.
 *
 * Boot sequence (called once by Webkernel\ServiceProvider::boot)
 * --------------------------------------------------------------
 *   bootHelpers()    -- require_once helper files shipped with the core
 *   bootRoutes()     -- register core web / api routes
 *   bootConfig()     -- merge core configuration files
 *   bootViews()      -- load core blade view namespaces
 *   bootLang()       -- load core translation files
 *   bootMigrations() -- load core database migrations
 *   bootLivewire()   -- auto-register core livewire components
 *   bootCommands()   -- register core artisan commands discovered by path
 *   bootBlaze()      -- apply blaze optimisations for core views
 *
 * Adding a new core asset type requires only a new boot*() method here.
 * Nothing else needs to change.
 *
 * @since Webkernel Waterfall (v1.x.x)
 */
final class Platform
{
    public function __construct(
        private readonly Application $app,
    ) {}

    /**
     * Run the full core boot sequence.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->bootHelpers();
        $this->bootRoutes();
        $this->bootConfig();
        $this->bootViews();
        $this->bootLang();
        $this->bootMigrations();
        $this->bootLivewire();
        $this->bootCommands();
        $this->bootBlaze();
    }

    // -------------------------------------------------------------------------
    // Boot steps
    // -------------------------------------------------------------------------

    /**
     * Require core helper files.
     *
     * Scans WEBKERNEL_HELPER_PATHS when defined, then falls back to the
     * conventional helpers directory directly under WEBKERNEL_PATH.
     *
     * @return void
     */
    private function bootHelpers(): void
    {
        $paths = \defined('WEBKERNEL_HELPER_PATHS') ? WEBKERNEL_HELPER_PATHS : [];

        if (empty($paths)) {
            $default = WEBKERNEL_PATH . '/src/helpers';
            if (\is_dir($default)) {
                $paths[] = $default;
            }
        }

        foreach ($paths as $dir) {
            if (! \is_dir($dir)) {
                continue;
            }
            foreach (\glob($dir . '/*.php') ?: [] as $file) {
                require_once $file;
            }
        }
    }

    /**
     * Register core web and api routes.
     *
     * Route files are resolved from WEBKERNEL_ROUTE_PATHS when defined.
     * Each entry may carry a 'group' key ('web', 'api') and a 'file' key.
     *
     * @return void
     */
    private function bootRoutes(): void
    {
        $routes = \defined('WEBKERNEL_ROUTE_PATHS') ? WEBKERNEL_ROUTE_PATHS : [];

        foreach ($routes as $spec) {
            $file  = \is_array($spec) ? (string) ($spec['file'] ?? '') : (string) $spec;
            $group = \is_array($spec) ? (string) ($spec['group'] ?? '') : '';

            if (! \is_file($file)) {
                continue;
            }

            match ($group) {
                'api'   => Route::middleware('api')->group($file),
                'web'   => Route::middleware('web')->group($file),
                default => Route::group([], $file),
            };
        }
    }

    /**
     * Merge core configuration files.
     *
     * Scans WEBKERNEL_CONFIG_PATH for *.php files and merges each one under
     * its filename stem as the config key, without overriding user values.
     *
     * @return void
     */
    private function bootConfig(): void
    {
        $dir = \defined('WEBKERNEL_CONFIG_PATH') ? WEBKERNEL_CONFIG_PATH : WEBKERNEL_PATH . '/config';

        if (! \is_dir($dir)) {
            return;
        }

        foreach (\glob($dir . '/*.php') ?: [] as $file) {
            $key = \pathinfo($file, \PATHINFO_FILENAME);
            if (! $this->app['config']->has($key)) {
                $this->app['config']->set($key, require $file);
            }
        }
    }

    /**
     * Load core blade view namespaces.
     *
     * Namespaces are resolved from WEBKERNEL_VIEW_NAMESPACES when defined.
     * Each entry is expected to be an associative array of handle => path.
     *
     * @return void
     */
    private function bootViews(): void
    {
        $namespaces = \defined('WEBKERNEL_VIEW_NAMESPACES') ? WEBKERNEL_VIEW_NAMESPACES : [];

        foreach ($namespaces as $handle => $path) {
            if (\is_dir($path)) {
                $this->app['view']->addNamespace((string) $handle, $path);
            }
        }
    }

    /**
     * Load core translation files.
     *
     * Paths are resolved from WEBKERNEL_LANG_PATHS when defined.
     * Each entry may be a bare path string or an associative array with
     * 'handle' and 'path' keys.
     *
     * @return void
     */
    private function bootLang(): void
    {
        $entries = \defined('WEBKERNEL_LANG_PATHS') ? WEBKERNEL_LANG_PATHS : [];

        foreach ($entries as $handle => $path) {
            $dir = (string) $path;
            if (! \is_dir($dir)) {
                continue;
            }
            $this->app['translator']->addNamespace((string) $handle, $dir);
            $this->app['translator']->addJsonPath($dir);
        }
    }

    /**
     * Load core database migrations.
     *
     * Always loads the migrations directory that ships inside the webkernel
     * package.  Additional paths can be declared via WEBKERNEL_MIGRATION_PATHS.
     *
     * @return void
     */
    private function bootMigrations(): void
    {
        $paths = [];

        $core = WEBKERNEL_UPPERPATH . '/database/migrations';
        if (\is_dir($core)) {
            $paths[] = $core;
        }

        $extra = \defined('WEBKERNEL_MIGRATION_PATHS') ? WEBKERNEL_MIGRATION_PATHS : [];
        foreach ($extra as $path) {
            $dir = (string) $path;
            if (\is_dir($dir)) {
                $paths[] = $dir;
            }
        }

        foreach (\array_unique($paths) as $path) {
            $this->app['migrator']->path($path);
        }
    }

    /**
     * Auto-register core livewire components found under WEBKERNEL_LIVEWIRE_PATHS.
     *
     * Skipped entirely when Livewire is not installed.
     *
     * @return void
     */
    private function bootLivewire(): void
    {
        if (! \class_exists(Livewire::class)) {
            return;
        }

        $entries = \defined('WEBKERNEL_LIVEWIRE_PATHS') ? WEBKERNEL_LIVEWIRE_PATHS : [];

        foreach ($entries as $namespace => $dir) {
            $dir = (string) $dir;
            if (! \is_dir($dir)) {
                continue;
            }

            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            );

            foreach ($it as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $class = \str_replace(
                    [\DIRECTORY_SEPARATOR, '.php'],
                    ['\\', ''],
                    \str_replace($dir . \DIRECTORY_SEPARATOR, '', $file->getPathname()),
                );

                $fqcn = \rtrim((string) $namespace, '\\') . '\\' . $class;

                if (\class_exists($fqcn)) {
                    Livewire::component(
                        \str_replace(['\\', '_'], ['.', '-'], \strtolower($class)),
                        $fqcn,
                    );
                }
            }
        }
    }

    /**
     * Register core artisan commands discovered by scanning WEBKERNEL_COMMAND_PATHS.
     *
     * Skipped when not running in console.
     *
     * @return void
     */
    private function bootCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $entries = \defined('WEBKERNEL_COMMAND_PATHS') ? WEBKERNEL_COMMAND_PATHS : [];

        /** @var list<class-string<\Illuminate\Console\Command>> $commands */
        $commands = [];

        foreach ($entries as $dir) {
            $dir = (string) $dir;
            if (! \is_dir($dir)) {
                continue;
            }

            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            );

            foreach ($it as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $fqcn = extractClassFromFile($file->getPathname());

                if ($fqcn !== null && \is_subclass_of($fqcn, \Illuminate\Console\Command::class)) {
                    $commands[] = $fqcn;
                }
            }
        }

        if (! empty($commands)) {
            $this->app[\Illuminate\Contracts\Console\Kernel::class]->registerCommand(
                ...\array_map(static fn (string $c) => new $c(), $commands),
            );
        }
    }

    /**
     * Apply blaze optimisations for core view paths.
     *
     * Skipped when Blaze is not bound in the container.
     *
     * @return void
     */
    private function bootBlaze(): void
    {
        if (! $this->app->bound('blaze')) {
            return;
        }

        $entries = \defined('WEBKERNEL_BLAZE_PATHS') ? WEBKERNEL_BLAZE_PATHS : [];

        if (empty($entries)) {
            return;
        }

        $optimizer = Blaze::optimize();

        foreach ($entries as $spec) {
            $dir     = (string) ($spec['path']    ?? $spec);
            $compile = (bool)   ($spec['compile'] ?? true);
            $memo    = (bool)   ($spec['memo']    ?? false);
            $fold    = (bool)   ($spec['fold']    ?? false);
            $safe    = (string) ($spec['safe']    ?? '');
            $unsafe  = (string) ($spec['unsafe']  ?? '');

            if (! \is_dir($dir)) {
                continue;
            }

            if (! $compile) {
                $optimizer->in($dir, compile: false);
                continue;
            }

            if ($fold) {
                $optimizer->in($dir, true, ...\array_filter([
                    'safe'   => $safe   !== '' ? $safe   : null,
                    'unsafe' => $unsafe !== '' ? $unsafe : null,
                ]));
                continue;
            }

            if ($memo) {
                $optimizer->in($dir, memo: true);
                continue;
            }

            $optimizer->in($dir);
        }
    }
}
