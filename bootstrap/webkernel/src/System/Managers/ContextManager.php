<?php declare(strict_types=1);

namespace Webkernel\System\Managers;

use Filament\Facades\Filament;
use Webkernel\System\Contracts\Managers\ContextManagerInterface;

/**
 * Request context classification.
 *
 * @internal
 */
final class ContextManager implements ContextManagerInterface
{
    public function isPanel(string $panelId): bool
    {
        if (!class_exists(Filament::class)) {
            return false;
        }

        try {
            return Filament::getCurrentPanel()?->getId() === $panelId;
        } catch (\Throwable) {
            return false;
        }
    }

    public function isCli(): bool
    {
        return PHP_SAPI === 'cli' || PHP_SAPI === 'cli-server';
    }

    public function isApi(): bool
    {
        try {
            return request()->is('api/*') || request()->routeIs('api.*');
        } catch (\Throwable) {
            return false;
        }
    }

    public function isInternal(): bool
    {
        if ($this->isCli()) {
            return true;
        }

        try {
            $ip = request()->ip() ?? '';
        } catch (\Throwable) {
            return false;
        }

        return in_array($ip, ['127.0.0.1', '::1'], true)
            || str_starts_with($ip, '10.')
            || str_starts_with($ip, '192.168.')
            || preg_match('/^172\.(1[6-9]|2\d|3[01])\./', $ip) === 1;
    }

    public function hasRole(string ...$roles): bool
    {
        try {
            $user = filament()->auth()->user();
        } catch (\Throwable) {
            return false;
        }

        if ($user === null) {
            return false;
        }

        if (!method_exists($user, 'hasRole')) {
            return false;
        }

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }
}
