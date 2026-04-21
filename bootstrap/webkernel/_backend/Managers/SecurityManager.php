<?php declare(strict_types=1);

namespace Webkernel\System\Managers;

use Webkernel\System\Contracts\Managers\SecurityManagerInterface;

/**
 * Metric access control and value masking.
 *
 * Sensitivity levels (ascending):
 *   public     — always readable
 *   internal   — admin panel only
 *   restricted — authenticated admin only
 *   critical   — CLI / trusted contexts only
 *
 * @internal
 */
final class SecurityManager implements SecurityManagerInterface
{
    public function isProduction(): bool
    {
        return app()->isProduction();
    }

    public function canAccess(string $level): bool
    {
        return match ($level) {
            'public'     => true,
            'internal'   => !$this->isProduction() || $this->isAuthenticatedAdmin(),
            'restricted' => $this->isAuthenticatedAdmin(),
            'critical'   => !$this->isProduction(),
            default      => false,
        };
    }

    public function maskIp(string $ip): string
    {
        if (!$this->productionMode()) {
            return $ip;
        }

        $parts = explode('.', $ip);

        if (count($parts) === 4) {
            return $parts[0] . '.xxx.xxx.xxx';
        }

        return 'xxx.xxx.xxx.xxx';
    }

    public function maskPath(string $path): string
    {
        if (!$this->productionMode()) {
            return $path;
        }

        $basename = basename($path);
        $base     = strlen($path) > strlen($basename) + 1 ? '/**/' : '/';

        return $base . $basename;
    }

    public function maskVersion(string $version): string
    {
        if (!$this->productionMode()) {
            return $version;
        }

        $parts = explode('.', $version);

        if (count($parts) >= 3) {
            return $parts[0] . '.' . $parts[1] . '.x';
        }

        return $version;
    }

    public function productionMode(): bool
    {
        return $this->isProduction() && !$this->isAuthenticatedAdmin();
    }

    /**
     * Determine whether the current request is an authenticated admin session.
     *
     * Returns true when:
     *   - auth() resolves and a user is authenticated, OR
     *   - the guard cannot be resolved (CLI context — all access allowed).
     */
    private function isAuthenticatedAdmin(): bool
    {
        try {
            return auth()->check();
        } catch (\Throwable) {
            return true;
        }
    }
}
