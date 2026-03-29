<?php declare(strict_types=1);

namespace Webkernel\Aptitudes\System\Concerns;

use Illuminate\Support\Facades\Artisan;

/**
 * Maintenance action methods for the Maintenance page.
 *
 * Mix into a Livewire page component to get standard cache clear,
 * optimize, and related artisan-triggered actions.
 *
 * All actions dispatch wk-toast events consumed by the Blade bridge.
 */
trait HasMaintenanceActions
{
    /**
     * Clear application caches: config, route, view, event, application.
     */
    public function clearApplicationCache(): void
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('event:clear');

        $this->dispatch('wk-toast', type: 'success', message: 'All caches cleared.');
        $this->dispatch('wk-system-action', action: 'cache:clear', performed_by: $this->currentUserName());
    }

    /**
     * Cache config, routes, views and events for production performance.
     */
    public function optimizeApplication(): void
    {
        Artisan::call('optimize');

        $this->dispatch('wk-toast', type: 'success', message: 'Application optimized.');
        $this->dispatch('wk-system-action', action: 'optimize', performed_by: $this->currentUserName());
    }

    /**
     * Refresh the php.net releases cache on demand.
     */
    public function refreshPhpReleases(): void
    {
        Artisan::call('webkernel:refresh-php-releases');

        $this->dispatch('wk-toast', type: 'info', message: 'PHP releases cache refreshed.');
    }

    /**
     * Return the current authenticated user's display name for audit events.
     */
    private function currentUserName(): ?string
    {
        try {
            $user = filament()->auth()->user();

            if ($user === null) {
                return null;
            }

            return $user->name ?? $user->email ?? null;
        } catch (\Throwable) {
            return null;
        }
    }
}
