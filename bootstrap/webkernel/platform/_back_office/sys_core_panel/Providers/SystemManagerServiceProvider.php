<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Webkernel\System\Contracts\WebAppInterface;
use Webkernel\System\Contracts\Managers\AppManagerInterface;
use Webkernel\System\Contracts\Managers\AuthManagerInterface;
use Webkernel\System\Contracts\Managers\ContextManagerInterface;
use Webkernel\System\Contracts\Managers\HostManagerInterface;
use Webkernel\System\Contracts\Managers\InstanceManagerInterface;
use Webkernel\System\Contracts\Managers\OsManagerInterface;
use Webkernel\System\Contracts\Managers\RuntimeManagerInterface;
use Webkernel\System\Contracts\Managers\SecurityManagerInterface;
use Webkernel\System\Contracts\Managers\UsersManagerInterface;
use Webkernel\System\Contracts\Managers\VersionManagerInterface;
use Webkernel\System\Managers\VersionManager;
use Webkernel\System\WebernelAPI;
use Webkernel\System\Managers\AppManager;
use Webkernel\System\Managers\AuthManager;
use Webkernel\System\Managers\ContextManager;
use Webkernel\System\Managers\HostManager;
use Webkernel\System\Managers\InstanceManager;
use Webkernel\System\Managers\OsManager;
use Webkernel\System\Managers\RuntimeManager;
use Webkernel\System\Managers\SecurityManager;
use Webkernel\System\Managers\UsersManager;
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
        $this->app->singleton(WebAppInterface::class, WebernelAPI::class);
        $this->app->singleton(WebernelAPI::class);

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
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/system-manager.php',
            'webkernel-system',
        );

        $this->mergeConfigFrom(
            __DIR__ . '/../Config/webkernel-auth.php',
            'webkernel-auth',
        );

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
            \Webkernel\System\Console\DetectCapabilities::class,
            \Webkernel\System\Console\Install::class,
            \Webkernel\System\Console\RefreshPhpReleasesCache::class,
        ]);

        // ── Octane worker lifecycle hooks ─────────────────────────────────────
        // Rebuild CapabilityMap and flush static caches once per new worker,
        // so each worker reads a fresh deployment.php.
        if (class_exists(\Laravel\Octane\Events\WorkerStarting::class)) {
            \Illuminate\Support\Facades\Event::listen(
                \Laravel\Octane\Events\WorkerStarting::class,
                static function (): void {
                    \Webkernel\System\Support\CapabilityMap::reset();
                    \Webkernel\System\Support\CapabilityMap::get();  // pre-warm
                    \Webkernel\System\Support\StaticDataCache::reset();
                }
            );
        }
    }
}
