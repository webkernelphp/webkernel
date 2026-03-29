<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use Filament\Pages\Dashboard;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Webkernel\Panel;
use Webkernel\PanelProvider;

class AppPanelProvider extends PanelProvider
{
    protected string $panelId   = 'app';
    protected string $panelPath = 'app';

    protected array $panelDefaults = [
        'brand_logo'        => '/logo.png',
        'brand_logo_dark'   => '/logo-dark.png',
        'primary_color'     => 'Filament\\Support\\Colors\\Color::Amber',
        'auth'              => 1,
        'dark_mode'         => 1,
        'global_search'     => 1,
        'is_default'        => 0,
    ];

    protected function build(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([AccountWidget::class, FilamentInfoWidget::class]);
    }
}
