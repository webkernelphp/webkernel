<?php

declare(strict_types=1);

namespace Webkernel\Aptitudes\System\Providers;

use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Webkernel\Pages\Dashboard;
use Webkernel\Pages\DependencyManager;
use Webkernel\Panel;
use Webkernel\PanelProvider;

final class SystemPanelProvider extends PanelProvider
{
    protected string $panelId   = 'system';
    protected string $panelPath = 'system';

    protected bool $acceptRemoteComponents = true;

    /**
     * Initial config written to storage/webkernel/panels/system.json on first boot.
     * Edit the file or update via PanelConfigStore::patch() to override at runtime.
     */
    protected array $panelDefaults = [
        'brand_logo'            => '/logo.png',
        'brand_logo_dark'       => '/logo-dark.png',
        'brand_logo_height'     => '2.1rem',
        'max_content_width'     => 'screen-2xl',
        'sidebar_collapsible'   => 1,
        'spa'                   => 1,
        'dark_mode'             => 1,
        'global_search'         => 1,
        'auth'                  => 1,
        'registration'          => 1,
        'is_default'            => 1,
    ];

    /**
     * Structural config only: discovery paths, pages, widgets.
     * Brand, layout, features, and auth come from $panelDefaults / the config store.
     */
    protected function build(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->profile(isSimple: false)
            ->discoverResources(
                in: aptitude_path('System/Presentation/Resources'),
                for: 'Webkernel\Aptitudes\System\Presentation\Resources',
            )
            ->discoverPages(
                in: aptitude_path('System/Presentation/Pages'),
                for: 'Webkernel\Aptitudes\System\Presentation\Pages',
            )
            ->pages([Dashboard::class, DependencyManager::class])
            ->discoverWidgets(
                in: aptitude_path('System/Presentation/Widgets'),
                for: 'Webkernel\Aptitudes\System\Presentation\Widgets',
            )
            ->widgets([AccountWidget::class, FilamentInfoWidget::class]);
    }
}
