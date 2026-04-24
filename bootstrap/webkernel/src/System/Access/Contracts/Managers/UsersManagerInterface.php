<?php declare(strict_types=1);

namespace Webkernel\System\Access\Contracts\Managers;

use Illuminate\Contracts\Auth\Authenticatable;
use Webkernel\Users\Enum\UserOrigin;
use Webkernel\Users\Enum\UserPrivilegeLevel;
use Webkernel\Users\Models\User;

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
 *   webkernel()->users()->current()                        → ?User
 *   webkernel()->users()->current()->isExternal()          → bool
 *   webkernel()->users()->current()->isInternal()          → bool
 *   webkernel()->users()->current()->getPrivilegeLevel()   → ?UserPrivilegeLevel
 *   webkernel()->users()->currentLevel()                   → ?UserPrivilegeLevel
 *   webkernel()->users()->hasAtLeast(UserPrivilegeLevel::SYSADMIN)
 *   webkernel()->users()->hasOwner()                       → bool
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
     * The currently authenticated platform User, or null for guests.
     */
    public function current(): ?User;

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

    // ── Instance-level queries ────────────────────────────────────────────────

    /**
     * True when at least one APP_OWNER exists on this platform instance.
     */
    public function hasOwner(): bool;
}
