<?php

namespace Webkernel\Base\Builders\DBStudio\Enums;

enum CurveType: string
{
    case Smooth = 'smooth';
    case Straight = 'straight';
    case Stepline = 'stepline';

    public function label(): string
    {
        return match ($this) {
            self::Smooth => 'Smooth',
            self::Straight => 'Straight',
            self::Stepline => 'Step Line',
        };
    }
}
