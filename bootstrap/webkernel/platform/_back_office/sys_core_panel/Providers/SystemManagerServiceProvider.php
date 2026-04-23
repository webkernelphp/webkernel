<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Webkernel\System\WebAppInterface;
use Webkernel\System\Host\Contracts\Managers\{
    HostManagerInterface, InstanceManagerInterface, OsManagerInterface,
    VersionManagerInterface};
use Webkernel\System\Access\Contracts\Managers\{
    AppManagerInterface, AuthManagerInterface, ContextManagerInterface,
    RuntimeManagerInterface, SecurityManagerInterface, UsersManagerInterface};
use Webkernel\System\WebkernelAPI;
use Webkernel\System\Host\Managers\{
    VersionManager, HostManager, InstanceManager, OsManager};
use Webkernel\System\Access\Managers\{
    AppManager, AuthManager, ContextManager, RuntimeManager,
    SecurityManager, UsersManager};
/**
 * Binds all Webkernel manager interfaces to their concrete implementations.
 *
 * OCTANE BINDING STRATEGY
 * ───────────────────────
 * singleton() — stable data, does not change per request:
 *   Kernel, InstanceManager, HostManager, OsManager
 *
 * scoped() — recycled per Octane request:
 *   RuntimeManager, AppManager, SecurityManager, ContextManager, AuthManager
 */
final class SystemManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Root API singleton
        $this->app->singleton(WebAppInterface::class, WebkernelAPI::class);
        $this->app->singleton(WebkernelAPI::class);

        // ── Stable singletons ─────────────────────────────────────────────────
        $this->app->singleton(InstanceManagerInterface::class, InstanceManager::class);
        $this->app->singleton(HostManagerInterface::class, HostManager::class);
        $this->app->singleton(OsManagerInterface::class, OsManager::class);
        $this->app->singleton(VersionManagerInterface::class, VersionManager::class);

        // ── Request-scoped bindings ───────────────────────────────────────────
        $this->app->scoped(RuntimeManagerInterface::class, RuntimeManager::class);
        $this->app->scoped(AppManagerInterface::class, AppManager::class);
        $this->app->scoped(SecurityManagerInterface::class, SecurityManager::class);
        $this->app->scoped(ContextManagerInterface::class, ContextManager::class);

        $this->app->scoped(AuthManagerInterface::class, function (): AuthManager {
            // Use the 'web' guard; override via config('webkernel-auth.guard')
            $guard = config('webkernel-auth.guard', 'web');

            return new AuthManager(
                Auth::guard($guard),
                $this->app->make(\Illuminate\Contracts\Auth\Access\Gate::class),
            );
        });

        $this->app->scoped(UsersManagerInterface::class, function (): UsersManager {
            $guard = config('webkernel-auth.guard', 'web');

            return new UsersManager(Auth::guard($guard));
        });
    }

    public function boot(): void
    {


        // ── Auto-migrate on fresh install ─────────────────────────────────────
        // Idempotent: Schema::hasTable() is a near-instant no-op once done.
        // Runs after all providers are booted so Artisan::call() is fully safe.
        // Skipped during artisan CLI to avoid re-entrant migrate calls.
        if (!$this->app->runningInConsole()) {
            $this->app->booted(static function (): void {
                try {
                    if (!\Illuminate\Support\Facades\Schema::hasTable('sessions')) {
                        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                    }
                } catch (\Throwable) {
                    // DB unreachable or not yet configured — skip silently
                }
            });
        }

        // ── Artisan commands ──────────────────────────────────────────────────
        // Registered unconditionally so Artisan::call() works from web requests
        // (e.g. the installer panel calling webkernel:install).
        $this->commands([
            \Webkernel\System\Host\Console\DetectCapabilities::class,
            \Webkernel\System\Host\Console\Install::class,
            \Webkernel\System\Host\Console\RefreshPhpReleasesCache::class,
        ]);

        // ── Octane worker lifecycle hooks ─────────────────────────────────────
        // Rebuild CapabilityMap and flush static caches once per new worker,
        // so each worker reads a fresh deployment.php.
        if (class_exists(\Laravel\Octane\Events\WorkerStarting::class)) {
            \Illuminate\Support\Facades\Event::listen(
                \Laravel\Octane\Events\WorkerStarting::class,
                static function (): void {
                    \Webkernel\System\Host\Support\CapabilityMap::reset();
                    \Webkernel\System\Host\Support\CapabilityMap::get();  // pre-warm
                    \Webkernel\System\Host\Support\StaticDataCache::reset();
                }
            );
        }
    }
}
