<?php declare(strict_types=1);

namespace Webkernel\Base\Arcanes\Modules\Enum;

enum ModuleStatus: string
{
    case ENABLED    = 'enabled';
    case DISABLED   = 'disabled';
    case INSTALLING = 'installing';
    case ERROR      = 'error';

    public function label(): string
    {
        return match ($this) {
            self::ENABLED    => 'Enabled',
            self::DISABLED   => 'Disabled',
            self::INSTALLING => 'Installing',
            self::ERROR      => 'Error',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ENABLED    => 'success',
            self::DISABLED   => 'gray',
            self::INSTALLING => 'warning',
            self::ERROR      => 'danger',
        };
    }

    public function isOperational(): bool
    {
        return $this === self::ENABLED;
    }
}
