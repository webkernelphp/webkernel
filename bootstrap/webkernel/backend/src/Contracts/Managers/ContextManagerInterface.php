<?php declare(strict_types=1);

namespace Webkernel\System\Contracts\Managers;

/**
 * Request context classification.
 *
 * Used internally by the security manager and by consuming code to
 * gate features, metrics, or UI sections per execution context.
 *
 * @api
 */
interface ContextManagerInterface
{
    /** True when the current request is served by the given Filament panel id. */
    public function isPanel(string $panelId): bool;

    /** True when running from the command line. */
    public function isCli(): bool;

    /** True when the current request matches an API route group. */
    public function isApi(): bool;

    /**
     * True when the request originates from a loopback address or
     * a trusted internal network range configured in the application.
     */
    public function isInternal(): bool;

    /**
     * True when the currently authenticated user holds one of the given
     * roles. Returns false when no user is authenticated.
     *
     * @param string ...$roles
     */
    public function hasRole(string ...$roles): bool;
}
