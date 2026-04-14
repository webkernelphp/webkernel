<?php declare(strict_types=1);

namespace Webkernel\System\Contracts\Managers;

/**
 * Metric access control and value masking.
 *
 * Every metric value that flows through the public API passes through
 * the security manager when the consuming code explicitly requests
 * authorization checks (e.g. masking IPs, blocking host metrics on
 * public API routes).
 *
 * Sensitivity levels (ascending):
 *   public     — safe to display anywhere
 *   internal   — safe inside admin panels only
 *   restricted — requires explicit admin grant
 *   critical   — masked in all but CLI / trusted contexts
 *
 * @api
 */
interface SecurityManagerInterface
{
    /** Whether the application is in production mode. */
    public function isProduction(): bool;

    /**
     * Whether the current context may read metrics at the given sensitivity level.
     *
     * @param string $level  One of: public | internal | restricted | critical
     */
    public function canAccess(string $level): bool;

    /**
     * Mask an IP address for display.
     * Returns "xxx.xxx.xxx.xxx" in production and restricted contexts.
     */
    public function maskIp(string $ip): string;

    /**
     * Mask a filesystem path for display.
     * Replaces intermediate directory segments with "***".
     */
    public function maskPath(string $path): string;

    /**
     * Mask a version string to major.minor only.
     * E.g. "8.4.3" → "8.4.x" in restricted contexts.
     */
    public function maskVersion(string $version): string;

    /**
     * Whether production-mode data scrubbing is active.
     * True when isProduction() AND the current request context is not
     * an authenticated internal panel session.
     */
    public function productionMode(): bool;
}
