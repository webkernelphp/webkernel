<?php

declare(strict_types=1);

namespace Webkernel\Pages;

use BackedEnum;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Pages\Page;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsIconAlias;
use Illuminate\Contracts\Support\Htmlable;
use Webkernel\Pages\Dashboard\Concerns\ResolvesWidgets;
use Filament\Pages\Dashboard\Actions\FilterAction;

class Dashboard extends Page
{


    public ?array $filters = [];

    protected static string $routePath = '/';

    protected static ?int $navigationSort = -2;

    protected static ?string $title = 'Dashboard';

    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ?? static::$title ?? 'Dashboard';
    }

    public static function getNavigationIcon(): string | BackedEnum | Htmlable | null
    {
        return static::$navigationIcon
            ?? FilamentIcon::resolve(PanelsIconAlias::PAGES_DASHBOARD_NAVIGATION_ITEM)
            ?? (Filament::hasTopNavigation() ? Heroicon::Home : Heroicon::OutlinedHome);
    }

    public static function getRoutePath(Panel $panel): string
    {
        return static::$routePath;
    }

    public function getColumns(): int | array
    {
        return [
            'md' => 4,
            'xl' => 6,
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getWidgetsComponent(),
        ]);
    }

    protected function getWidgetsComponent(): Component
    {
        return Grid::make($this->getColumns())
            ->schema([

            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            FilterAction::make('filters')
                ->schema([
                    \Filament\Forms\Components\DatePicker::make('startDate'),
                    \Filament\Forms\Components\DatePicker::make('endDate'),
                    \Filament\Forms\Components\Select::make('environment')
                        ->options([
                            'local' => 'Local',
                            'staging' => 'Staging',
                            'production' => 'Production',
                        ]),
                ])
                ->action(function (array $data): void {
                    $this->filters = $data;
                }),
        ];
    }
}
