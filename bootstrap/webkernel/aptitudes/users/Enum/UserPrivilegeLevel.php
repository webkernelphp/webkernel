<?php declare(strict_types=1);

namespace Webkernel\Users\Enum;

enum UserPrivilegeLevel: string
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
            self::APP_OWNER  => 'Ultimate authority over the application.',
            self::SUPER_USER => 'Full administrative access.',
            self::MEMBER     => 'Standard authenticated user.',
        };
    }

    public function isAbove(self $other): bool
    {
        return match ($this) {
            self::APP_OWNER  => $other !== self::APP_OWNER,
            self::SUPER_USER => $other === self::MEMBER,
            self::MEMBER     => false,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::APP_OWNER->value  => self::APP_OWNER->label(),
            self::SUPER_USER->value => self::SUPER_USER->label(),
            self::MEMBER->value     => self::MEMBER->label(),
        ];
    }

    public static function fromKey(string $key): ?self
    {
        return self::tryFrom($key);
    }
}
