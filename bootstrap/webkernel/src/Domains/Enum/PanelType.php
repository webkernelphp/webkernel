<?php declare(strict_types=1);

namespace Webkernel\Domains\Enum;

enum PanelType: string
{
    case SYSTEM   = 'system';
    case BUSINESS = 'business';
    case MODULE   = 'module';

    public function label(): string
    {
        return match ($this) {
            self::SYSTEM   => 'System Panel',
            self::BUSINESS => 'Business Panel',
            self::MODULE   => 'Module Panel',
        };
    }

    public function requiresModuleId(): bool
    {
        return $this === self::MODULE;
    }
}
