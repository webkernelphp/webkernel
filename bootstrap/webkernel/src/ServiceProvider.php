<?php declare(strict_types=1);
namespace Webkernel;

use Illuminate\Support\ServiceProvider as Laravel;
use Webkernel\Commands\CommandReplacer;
use Webkernel\Panel\Concerns\RemoteComponentRegistry;
use Webkernel\Panel\Store\PanelConfigStore;
use Webkernel\Panel\Support\DefaultRemoteComponentRegistry;
use Webkernel\Providers\FilamentRenderHooks;
use Webkernel\Providers\ViewPathsAndComponents;

/**
 * Root Webkernel service provider.
 *
 * Owns:
 *  - Container singletons for all Webkernel contracts.
 *  - View path / Blade component registration.
 *  - Filament render-hook registration.
 *
 * Does NOT own:
 *  - Module / aptitude booting  → {@see \Webkernel\Arcanes\Modules}
 *  - Artisan command overrides  → {@see \Webkernel\Arcanes\Commands\DeclareCommands}
 */
final class ServiceProvider extends Laravel
{
    /**
     * Register all Webkernel singletons into the service container.
     *
     * Singletons are bound here (not in `boot`) so they are available to any
     * provider that depends on them regardless of boot order.
     *
     * @return void
     */
    public function register(): void
    {
        // ── Panel ──────────────────────────────────────────────────────────────

        $this->app->singleton(
            RemoteComponentRegistry::class,
            DefaultRemoteComponentRegistry::class,
        );

        $this->app->singleton(
            PanelConfigStore::class,
            fn () => new PanelConfigStore(storage_path('webkernel/panels')),
        );

        // ── Command replacer ───────────────────────────────────────────────────

        /**
         * Bind CommandReplacer as a singleton so any service that needs to
         * trigger additional overrides at runtime can retrieve the same instance
         * rather than re-instantiating the static helper.
         *
         * @see CommandReplacer
         */
        $this->app->singleton(CommandReplacer::class);
    }

    /**
     * Boot view paths, Blade components, and Filament render hooks.
     *
     * @return void
     */
    public function boot(): void
    {
        (new ViewPathsAndComponents($this->app))->boot();
        (new FilamentRenderHooks())->boot();
    }
}
