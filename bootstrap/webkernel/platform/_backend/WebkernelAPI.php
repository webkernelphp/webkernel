<?php declare(strict_types=1);

namespace Webkernel\System;

use Webkernel\System\WebAppInterface;
use Webkernel\System\Host\Contracts\Managers\{
    HostManagerInterface, InstanceManagerInterface, OsManagerInterface,
    VersionManagerInterface};
use Webkernel\System\Access\Contracts\Managers\{
    AppManagerInterface, AuthManagerInterface, ContextManagerInterface,
    RuntimeManagerInterface, SecurityManagerInterface, UsersManagerInterface};
use Webkernel\System\Ops\OperationBuilder;

/**
 * Webkernel public API entry point.
 *
 * Resolved by the webkernel() helper. Bound in the container as a singleton
 * implementing WebAppInterface.
 *
 * OCTANE SAFETY
 * ─────────────
 * Managers are split into two categories:
 *
 * Stable (singleton) — data does not change per request:
 *   instance, host, os
 *
 * Request-scoped (scoped) — data changes per request or reads auth state:
 *   runtime, app, security, context, auth
 *
 * Stable managers are memoised in this class (safe because they are singletons).
 * Scoped managers are NEVER memoised here — they are re-resolved from the
 * container on every call so the scoped binding can hand back the correct
 * per-request instance.
 *
 * Usage:
 *   webkernel()->instance()->memory()->humanUsed()
 *   webkernel()->host()->cpu()->usage()
 *   webkernel()->os()->isLinux()
 *   webkernel()->auth()->user()
 *   webkernel()->auth()->fieldSensitivity('email', Customer::class)
 *   webkernel()->users()->installerRoleOptions()
 *   webkernel()->users()->hasAtLeast(UserPrivilegeLevel::SYSADMIN)
 */
final class WebkernelAPI implements WebAppInterface
{
    // Stable singletons — safe to memoise
    private ?InstanceManagerInterface $instanceManager = null;
    private ?HostManagerInterface     $hostManager     = null;
    private ?OsManagerInterface       $osManager       = null;
    private ?VersionManagerInterface  $versionManager  = null;

    // ── Stable managers (memoised) ────────────────────────────────────────────

    public function instance(): InstanceManagerInterface
    {
        return $this->instanceManager ??= app(InstanceManagerInterface::class);
    }

    public function host(): HostManagerInterface
    {
        return $this->hostManager ??= app(HostManagerInterface::class);
    }

    public function os(): OsManagerInterface
    {
        return $this->osManager ??= app(OsManagerInterface::class);
    }

    public function versions(): VersionManagerInterface
    {
        return $this->versionManager ??= app(VersionManagerInterface::class);
    }

    /**
     * Create a new operations builder for source operations.
     *
     * Usage:
     *   webkernel()->do()
     *       ->from(GitHubProvider::forWebkernel())
     *       ->stepBefore('Validate', fn($ctx) => ...)
     *       ->run()
     */
    public function do(): OperationBuilder
    {
        return OperationBuilder::make();
    }

    public function http(): mixed
    {
        // HTTP client integration (placeholder)
        return null;
    }

    /**
     * Shorthand for webkernel()->versions()->currentString().
     * Returns the currently installed Webkernel version string, e.g. "2.1.4".
     */
    public function version(): string
    {
        $app = app();

        if ($app instanceof \Webkernel\WebApp) {
            return $app->webkernelVersion();
        }

        return method_exists($app, 'webkernelVersion')
            ? (string) $app->webkernelVersion()
            : 'dev';
    }

    // ── Request-scoped managers (NEVER memoised, re-resolved per call) ────────

    public function runtime(): RuntimeManagerInterface
    {
        return app(RuntimeManagerInterface::class);
    }

    public function app(): AppManagerInterface
    {
        return app(AppManagerInterface::class);
    }

    public function security(): SecurityManagerInterface
    {
        return app(SecurityManagerInterface::class);
    }

    public function context(): ContextManagerInterface
    {
        return app(ContextManagerInterface::class);
    }

    public function auth(): AuthManagerInterface
    {
        return app(AuthManagerInterface::class);
    }

    public function users(): UsersManagerInterface
    {
        return app(UsersManagerInterface::class);
    }
}
