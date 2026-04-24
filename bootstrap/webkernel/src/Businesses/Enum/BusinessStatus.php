<?php declare(strict_types=1);

namespace Webkernel\Businesses\Enum;

enum BusinessStatus: string
{
    case PENDING   = 'pending';
    case ACTIVE    = 'active';
    case SUSPENDED = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::PENDING   => 'Pending',
            self::ACTIVE    => 'Active',
            self::SUSPENDED => 'Suspended',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING   => 'warning',
            self::ACTIVE    => 'success',
            self::SUSPENDED => 'danger',
        };
    }
}
