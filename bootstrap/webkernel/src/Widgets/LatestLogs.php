<?php

declare(strict_types=1);

namespace Webkernel\Widgets;

use Filament\Tables;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Webkernel\Widgets\Contracts\ConfigurableWidget;

class LatestLogs extends TableWidget implements ConfigurableWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    public static function getWidgetType(): string
    {
        return 'table';
    }

    public static function getLabel(): string
    {
        return 'Logs';
    }

    public static function getDefaultConfig(): array
    {
        return [];
    }

    protected function getTableQuery(): ?Builder
    {
        return null;
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('level')->badge(),
            Tables\Columns\TextColumn::make('message')->limit(50),
            Tables\Columns\TextColumn::make('created_at')->since(),
        ];
    }
}
