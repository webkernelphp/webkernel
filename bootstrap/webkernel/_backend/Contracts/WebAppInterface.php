<?php declare(strict_types=1);

namespace Webkernel\System\Contracts;

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
    /**
     * PHP process metrics: memory, OPcache, limits, PHP identity.
     */
    public function instance(): InstanceManagerInterface;

    /**
     * Machine-level metrics: CPU, RAM, disk, uptime, processes, entropy.
     */
    public function host(): HostManagerInterface;

    /**
     * OS identity: family, distro, architecture, kernel version.
     */
    public function os(): OsManagerInterface;

    /**
     * SAPI and server adapter context (FPM, Swoole, CLI, RoadRunner…).
     */
    public function runtime(): RuntimeManagerInterface;

    /**
     * Laravel application context: env, debug, drivers, timezone.
     */
    public function app(): AppManagerInterface;

    /**
     * Access control, value masking, production-mode detection.
     */
    public function security(): SecurityManagerInterface;

    /**
     * Panel/API/CLI context detection and internal-network checks.
     */
    public function context(): ContextManagerInterface;

    /**
     * Authentication, user identity, role checks, and AI-field ACL.
     */
    public function auth(): AuthManagerInterface;

    /**
     * Platform-level user management: installer role setup, privilege queries.
     *
     * webkernel()->users()->installerRoleOptions()
     * webkernel()->users()->createWithPrivilege(name: ..., email: ..., password: ..., level: ...)
     * webkernel()->users()->currentLevel()
     * webkernel()->users()->hasAtLeast(UserPrivilegeLevel::SYSADMIN)
     */
    public function users(): UsersManagerInterface;

    /**
     * Webkernel version and release information.
     *
     * webkernel()->versions()->current()   // VersionInfo for the running version
     * webkernel()->versions()->latest()    // VersionInfo for the latest known release
     * webkernel()->versions()->releases()  // VersionInfo[] all known releases
     * webkernel()->versions()->hasUpdate() // bool
     */
    public function versions(): VersionManagerInterface;

    /**
     * Shorthand: currently installed Webkernel version string.
     *
     * Equivalent to webkernel()->versions()->currentString()
     * Example: "2.1.4"
     */
    public function version(): string;
}
