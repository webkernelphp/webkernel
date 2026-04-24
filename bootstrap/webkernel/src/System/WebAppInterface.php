<?php declare(strict_types=1);

namespace Webkernel\System;

use Webkernel\System\Host\Contracts\Managers\{
    HostManagerInterface, InstanceManagerInterface, OsManagerInterface,
    VersionManagerInterface};
use Webkernel\System\Access\Contracts\Managers\{
    AppManagerInterface, AuthManagerInterface, ContextManagerInterface,
    RuntimeManagerInterface, SecurityManagerInterface, UsersManagerInterface};

/**
 * Webkernel public API surface.
 *
 * Every public manager is declared here.
 * This interface is the only stable contract consumers should depend on.
 *
 * webkernel() resolves the singleton implementing this interface.
 *
 * @api
 */
interface WebAppInterface
{
    // ── HOST LAYER (stable) ───────────────────────────────────────────────────

    /**
     * PHP process metrics: memory, OPcache, limits, PHP identity.
     */
    public function instance(): InstanceManagerInterface;

    /**
     * Server hardware metrics: CPU, disk, memory, network.
     */
    public function host(): HostManagerInterface;

    /**
     * Operating system type and properties.
     */
    public function os(): OsManagerInterface;

    /**
     * Version information (Webkernel, PHP, extensions).
     */
    public function versions(): VersionManagerInterface;

    /**
     * Currently installed version string (shorthand).
     */
    public function version(): string;

    // ── OPS LAYER (operations & integrations) ──────────────────────────────────

    /**
     * Fluent operation builder for source operations, downloads, orchestration.
     */
    public function do(): mixed; // OperationBuilder

    /**
     * HTTP client manager for outbound requests.
     */
    public function http(): mixed; // HttpManager

    // ── ACCESS LAYER (request-scoped) ──────────────────────────────────────────

    /**
     * Runtime environment: Octane, PHP version, CLI vs web.
     */
    public function runtime(): RuntimeManagerInterface;

    /**
     * Application state: name, env, debug mode, booted modules.
     */
    public function app(): AppManagerInterface;

    /**
     * Security policies: encryption, CSRF, rate limiting.
     */
    public function security(): SecurityManagerInterface;

    /**
     * Request context: locale, timezone, user permissions.
     */
    public function context(): ContextManagerInterface;

    /**
     * Authentication: current user, roles, abilities.
     */
    public function auth(): AuthManagerInterface;

    /**
     * User management: roles, privileges, directory.
     */
    public function users(): UsersManagerInterface;
}
