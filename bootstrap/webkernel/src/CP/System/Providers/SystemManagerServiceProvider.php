<?php declare(strict_types=1);

namespace Webkernel\CP\System\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Webkernel\Base\System\Access\Contracts\Managers\{
    UsersManagerInterface, AuthManagerInterface};
use Webkernel\Base\System\Access\Managers\{
    UsersManager, AuthManager};

final class SystemManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Root API singleton
        $this->app->singleton(\Webkernel\Base\System\WebkernelAPI::class);

        // Bind managers used by WebkernelAPI
        $this->app->scoped(UsersManagerInterface::class, function (): UsersManager {
            $guard = config('webkernel-auth.guard', 'web');
            return new UsersManager(Auth::guard($guard));
        });

        $this->app->scoped(AuthManagerInterface::class, function (): AuthManager {
            $guard = config('webkernel-auth.guard', 'web');
            return new AuthManager(
                Auth::guard($guard),
                $this->app->make(\Illuminate\Contracts\Auth\Access\Gate::class),
            );
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
        $this->commands([]);

        // ── Octane worker lifecycle hooks ─────────────────────────────────────
        // Rebuild CapabilityMap and flush static caches once per new worker,
        // so each worker reads a fresh deployment.php.
        if (class_exists(\Laravel\Octane\Events\WorkerStarting::class)) {
            \Illuminate\Support\Facades\Event::listen(
                \Laravel\Octane\Events\WorkerStarting::class,
                static function (): void {
                    \Webkernel\Base\System\Host\Support\CapabilityMap::reset();
                    \Webkernel\Base\System\Host\Support\CapabilityMap::get();  // pre-warm
                    \Webkernel\Base\System\Host\Support\StaticDataCache::reset();
                }
            );
        }
    }
}
