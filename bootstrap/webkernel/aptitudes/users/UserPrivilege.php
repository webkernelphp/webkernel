<?php
declare(strict_types=1);

namespace Webkernel\Users;

/**
 * Core privilege levels defining the access control hierarchy.
 * These are NOT stored in database - they are runtime privilege markers.
 * Hierarchy: APP_OWNER > SUPER_USER > MEMBER
 */
enum UserPrivilege: string
{
    case APP_OWNER  = 'app-owner';
    case SUPER_USER = 'super-user';
    case MEMBER     = 'member';

    public function label(): string
    {
        return match ($this) {
            self::APP_OWNER  => 'Application Owner',
            self::SUPER_USER => 'Super Administrator',
            self::MEMBER     => 'Member',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::APP_OWNER  => 'Ultimate authority over the application. Immutable. Full access to all panels.',
            self::SUPER_USER => 'Full platform access with administrative privileges.',
            self::MEMBER     => 'Authenticated user with granted permissions.',
        };
    }

    public function scope(): string
    {
        return match ($this) {
            self::APP_OWNER, self::SUPER_USER => 'platform',
            self::MEMBER                      => 'user',
        };
    }

    public function isImmutable(): bool
    {
        return $this === self::APP_OWNER;
    }

    public function parent(): ?self
    {
        return match ($this) {
            self::SUPER_USER => self::APP_OWNER,
            self::MEMBER     => self::SUPER_USER,
            self::APP_OWNER  => null,
        };
    }

    public function children(): array
    {
        return match ($this) {
            self::APP_OWNER  => [self::SUPER_USER],
            self::SUPER_USER => [self::MEMBER],
            self::MEMBER     => [],
        };
    }

    public function isAbove(self $other): bool
    {
        $parent = $other->parent();
        while ($parent !== null) {
            if ($parent === $this) {
                return true;
            }
            $parent = $parent->parent();
        }
        return false;
    }

    public function isBelow(self $other): bool
    {
        return $other->isAbove($this);
    }

    public static function options(): array
    {
        return array_combine(
            array_map(fn(self $role): string => $role->value, self::cases()),
            array_map(fn(self $role): string => $role->label(), self::cases()),
        );
    }

    public static function optionsByScope(): array
    {
        $grouped = [];
        foreach (self::cases() as $role) {
            $scope = $role->scope();
            $grouped[$scope][$role->value] = $role->label();
        }
        return $grouped;
    }

    public static function exists(string $roleKey): bool
    {
        return self::tryFrom($roleKey) !== null;
    }

    public static function fromKey(string $roleKey): ?self
    {
        return self::tryFrom($roleKey);
    }
}
