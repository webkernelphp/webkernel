<?php declare(strict_types=1);

namespace Webkernel\System\Access\Managers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Hash;
use Webkernel\System\Access\Contracts\Managers\UsersManagerInterface;
use Webkernel\Users\Enum\UserOrigin;
use Webkernel\Users\Enum\UserPrivilegeLevel;
use Webkernel\Users\Models\User;

/**
 * Platform-level user management.
 *
 * Bound as scoped() — re-resolved per Octane request so currentLevel()
 * and related queries always reflect the active auth state.
 *
 * Usage:
 *   webkernel()->users()->installerRoleOptions()
 *   webkernel()->users()->createWithPrivilege(...)
 *   webkernel()->users()->currentLevel()
 *   webkernel()->users()->hasAtLeast(UserPrivilegeLevel::SYSADMIN)
 */
final class UsersManager implements UsersManagerInterface
{
    /** Privilege levels shown in the installer role selector, in display order. */
    // private const INSTALLER_ROLES = [
    //     UserPrivilegeLevel::APP_OWNER,
    //     UserPrivilegeLevel::SUPER_ADMIN,
    //     UserPrivilegeLevel::SYSADMIN,
    //     UserPrivilegeLevel::EXTERNAL_SUPER_ADMIN,
    //     UserPrivilegeLevel::EXTERNAL_SYSADMIN,
    // ];

    public function __construct(private readonly Guard $guard) {}

    // ── Installer helpers ─────────────────────────────────────────────────────

    /**
     * @return array<string, string>
     */
    public function installerRoleOptions(): array
    {
        return [
            UserPrivilegeLevel::APP_OWNER->value            => 'I am the App Owner',
            UserPrivilegeLevel::SUPER_ADMIN->value          => 'I am an internal super admin',
            UserPrivilegeLevel::SYSADMIN->value             => 'I am an internal sysadmin',
            UserPrivilegeLevel::EXTERNAL_SUPER_ADMIN->value => 'I am an external contractor (super admin)',
            UserPrivilegeLevel::EXTERNAL_SYSADMIN->value    => 'I am an external contractor (sysadmin)',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function installerRoleDescriptions(): array
    {
        return [
            UserPrivilegeLevel::APP_OWNER->value            => 'You own this instance. Your account gets full ownership rights.',
            UserPrivilegeLevel::SUPER_ADMIN->value          => 'Internal member with full admin access — the actual owner can be assigned later.',
            UserPrivilegeLevel::SYSADMIN->value             => 'Internal system administrator with technical control over infrastructure.',
            UserPrivilegeLevel::EXTERNAL_SUPER_ADMIN->value => 'External contractor deploying for a client. Account flagged as external — the actual owner can be assigned later.',
            UserPrivilegeLevel::EXTERNAL_SYSADMIN->value    => 'External contractor with scoped technical control. Account flagged as external.',
        ];
    }

    /**
     * Creates a platform user and bootstraps their privilege in one operation.
     *
     * @throws \Throwable
     */
    public function createWithPrivilege(
        string $name,
        string $email,
        string $password,
        UserPrivilegeLevel $level,
    ): Authenticatable {
        /** @var User $user */
        $user = User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
        ]);

        match (true) {
            $level === UserPrivilegeLevel::APP_OWNER => $user->bootstrapAsAppOwner(),

            $level->isInternal() => $user->grantPrivilege(
                level:  $level,
                origin: UserOrigin::INTERNAL,
            ),

            $level->isExternal() => $user->grantPrivilege(
                level:  $level,
                origin: UserOrigin::EXTERNAL,
            ),
        };

        return $user;
    }

    // ── Current-user queries ──────────────────────────────────────────────────

    public function current(): ?User
    {
        $user = $this->guard->user();

        return $user instanceof User ? $user : null;
    }

    public function currentLevel(): ?UserPrivilegeLevel
    {
        return $this->current()?->getPrivilegeLevel();
    }

    public function hasAtLeast(UserPrivilegeLevel $level): bool
    {
        $current = $this->currentLevel();

        return $current !== null && $current->isAtLeast($level);
    }

    public function is(UserPrivilegeLevel $level): bool
    {
        return $this->currentLevel() === $level;
    }

    public function isOrigin(UserOrigin $origin): bool
    {
        $current = $this->currentLevel();

        return $current !== null && $current->origin() === $origin;
    }

    public function hasOwner(): bool
    {
        return User::whereHas('privilegeRelation', fn ($q) =>
            $q->where('privilege', UserPrivilegeLevel::APP_OWNER->value)
        )->exists();
    }
}
