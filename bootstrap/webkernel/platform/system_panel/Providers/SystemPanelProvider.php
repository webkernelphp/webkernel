<?php declare(strict_types=1);

namespace Webkernel\Platform\SystemPanel\Providers;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Webkernel\Pages\Dashboard;
use Webkernel\Pages\DependencyManager;
use Filament\Support\Enums\Width;
use Filament\Support\Colors\Color;

final class SystemPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('system')
            ->path('system')
            ->default()
            ->favicon(webkernelBrandingUrl('webkernel-favicon'))
            ->brandLogo(webkernelBrandingUrl('webkernel-logo-light'))
            ->darkModeBrandLogo(webkernelBrandingUrl('webkernel-logo-dark'))
            ->brandLogoHeight('2.1rem')
            ->darkMode(true)
            ->maxContentWidth(Width::Full)
            ->sidebarWidth("17rem")
            ->spa()
            ->globalSearch(position: \Filament\Enums\GlobalSearchPosition::Sidebar)
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('screen-2xl')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->login()
            ->registration()
            ->profile(isSimple: false)
            ->discoverResources(
                in: aptitude_path('System/Presentation/Resources'),
                for: 'Webkernel\Platform\SystemPanel\Presentation\Resources',
            )
            ->discoverPages(
                in: aptitude_path('System/Presentation/Pages'),
                for: 'Webkernel\Platform\SystemPanel\Presentation\Pages',
            )
            ->pages([Dashboard::class, DependencyManager::class])
            ->discoverWidgets(
                in: aptitude_path('System/Presentation/Widgets'),
                for: 'Webkernel\Platform\SystemPanel\Presentation\Widgets',
            )
            ->widgets([AccountWidget::class, FilamentInfoWidget::class])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                AuthenticateSession::class,
            ]);
    }
}
