<?php declare(strict_types=1);

namespace Webkernel;

use Illuminate\Support\ServiceProvider as Laravel;
use Webkernel\Base\Arcanes\Modules;
use Webkernel\Base\Arcanes\Platform;
use Webkernel\Console\CommandReplacer;
use Webkernel\Console\RunJobCommand;
use Webkernel\Providers\FilamentRenderHooks;
use Webkernel\Providers\ViewPathsAndComponents;
use Webkernel\Base\System\Host\Console\DetectCapabilities;
use Webkernel\Base\System\Host\Console\Install;
use Webkernel\Base\System\Host\Console\RefreshPhpReleasesCache;

/**
 * Root webkernel service provider.
 *
 * Single entry point declared in WebApp::configure().
 *
 * Responsibilities
 * ----------------
 *   register() -- bind all webkernel singletons into the container.
 *                 Runs before any boot() so downstream providers can resolve them.
 *   boot()     -- orchestrate boot sequence in dependency order:
 *                   1. Platform  -- core webkernel assets (migrations, views, lang, etc.)
 *                   2. Modules   -- external modules and platform capabilities from catalog
 *                   3. Views     -- blade components, view paths, custom finder
 *                   4. Hooks     -- filament render hooks
 *
 * Does NOT own Module and platform discovery, Core webkernel asset booting,
 *   Artisan command overrides
 */
final class ServiceProvider extends Laravel
{
    /**
     * Register all webkernel singletons into the service container.
     *
     * Singletons are bound here so they are available to every provider
     * regardless of boot order.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(CommandReplacer::class);

        $this->app->register(Modules::class);
    }

    /**
     * Boot the platform in strict dependency order.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerCoreCommands();

        (new Platform($this->app))->boot();
        (new ViewPathsAndComponents($this->app))->boot();
        (new FilamentRenderHooks())->boot();
    }

    /**
     * Register commands that belong to the webkernel core itself.
     *
     * Replaces the standalone CommandServiceProvider which is no longer needed.
     *
     * @return void
     */
    private function registerCoreCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            RunJobCommand::class,
            DetectCapabilities::class,
            Install::class,
            RefreshPhpReleasesCache::class,
        ]);
    }
}
