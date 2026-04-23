<?php

declare(strict_types=1);

namespace Webkernel\BackOffice\System\Presentation\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Webkernel\Widgets\Contracts\ConfigurableWidget;

class SystemStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    public static function getWidgetType(): string
    {
        return 'stats';
    }

    public static function getLabel(): string
    {
        return 'System Stats';
    }

    public static function getDefaultConfig(): array
    {
        return [];
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Modules', \Webkernel\Query\QueryModules::make()->where('active')->is(true)->count()),
            Stat::make('Panels', count(filament()->getPanels())),
            Stat::make('Errors', 1000)->color('danger'),
        ];
    }
}
