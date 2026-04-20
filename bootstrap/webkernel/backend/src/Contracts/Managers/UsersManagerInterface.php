<?php declare(strict_types=1);

namespace Webkernel\System\Contracts\Managers;

use Illuminate\Contracts\Auth\Authenticatable;
use Webkernel\Users\Enum\UserOrigin;
use Webkernel\Users\Enum\UserPrivilegeLevel;

/**
 * Platform-level user management contract.
 *
 * Covers two concerns:
 *   1. Setup / installer — creating the first platform user and choosing their role.
 *   2. Runtime queries   — reading the current user's privilege level and origin.
 *
 * This manager operates on PLATFORM users only (@see UserPrivilegeLevel for scope).
 * Module-level end-users are handled by each module's own RBAC system.
 *
 * Usage:
 *   webkernel()->users()->installerRoleOptions()
 *   webkernel()->users()->createWithPrivilege(name: ..., email: ..., password: ..., level: ...)
 *   webkernel()->users()->currentLevel()
 *   webkernel()->users()->hasAtLeast(UserPrivilegeLevel::SYSADMIN)
 *
 * @api
 */
interface UsersManagerInterface
{
    // ── Installer helpers ─────────────────────────────────────────────────────

    /**
     * Returns the value → label map for the installer role selector.
     * Only the levels appropriate for the first-run setup wizard are included.
     *
     * @return array<string, string>
     */
    public function installerRoleOptions(): array;

    /**
     * Returns the value → description map for the installer role selector.
     *
     * @return array<string, string>
     */
    public function installerRoleDescriptions(): array;

    /**
     * Creates a new platform user and immediately bootstraps their privilege.
     *
     * Handles all privilege variants:
     *   APP_OWNER             → bootstrapAsAppOwner()
     *   SUPER_ADMIN/SYSADMIN  → grantPrivilege(level, INTERNAL)
     *   EXTERNAL_*            → grantPrivilege(level, EXTERNAL)
     *
     * @throws \Throwable on creation or privilege-grant failure.
     */
    public function createWithPrivilege(
        string $name,
        string $email,
        string $password,
        UserPrivilegeLevel $level,
    ): Authenticatable;

    // ── Current-user queries (request-scoped) ─────────────────────────────────

    /**
     * Privilege level of the currently authenticated user, or null for guests.
     */
    public function currentLevel(): ?UserPrivilegeLevel;

    /**
     * True when the current user holds at least the given privilege level
     * (rank-based comparison, origin-agnostic).
     */
    public function hasAtLeast(UserPrivilegeLevel $level): bool;

    /**
     * True when the current user holds exactly the given privilege level.
     */
    public function is(UserPrivilegeLevel $level): bool;

    /**
     * True when the current user belongs to the given origin.
     */
    public function isOrigin(UserOrigin $origin): bool;
}
