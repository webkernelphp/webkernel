<?php declare(strict_types=1);

namespace Webkernel\Base\System\Access\Managers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Hash;
use Webkernel\Base\System\Access\Contracts\Managers\UsersManagerInterface;
use Webkernel\Base\Users\Enums\UserOrigin;
use Webkernel\Base\Users\Enums\UserPrivilegeLevel;
use Webkernel\Base\Users\Models\User;
use Webkernel\Base\Users\Models\UserPrivilege;

final class UsersManager implements UsersManagerInterface
{
    public function __construct(private readonly Guard $guard) {}

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

    public function createWithPrivilege(
        string $name,
        string $email,
        string $password,
        UserPrivilegeLevel $level,
    ): Authenticatable {
        $user = User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
        ]);

        // Create the corresponding privilege record
        UserPrivilege::create([
            'user_id'     => $user->getKey(),
            'privilege'   => $level->value,
            'user_origin' => $level->origin()->value,
            'granted_by'  => $user->getKey(), // Self-grant for bootstrap
        ]);

        return $user;
    }

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
