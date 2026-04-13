<?php declare(strict_types=1);

namespace Webkernel\Platform\SystemPanel\Providers;

use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Session\Middleware\StartSession;
use Webkernel\Platform\SystemPanel\Presentation\Installer\InstallerPage;
use Filament\Support\Enums\Width;
use Filament\Support\Colors\Color;



/**
 * No-auth Filament panel for the first-run installation wizard.
 *
 * Accessible at /installer — no login required.
 * InstallerPage redirects to /system once the app is installed.
 */
final class InstallerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('installer')
            ->path('installer')
            ->brandName('Webkernel')
            ->topNavigation()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->favicon(webkernelBrandingUrl('webkernel-favicon'))
            ->brandLogo(webkernelBrandingUrl('webkernel-logo-light'))
            ->darkModeBrandLogo(webkernelBrandingUrl('webkernel-logo-dark'))
            ->sidebarFullyCollapsibleOnDesktop()
            ->maxContentWidth(Width::ExtraSmall)
            ->brandLogoHeight('2rem')
            ->pages([InstallerPage::class])
            ->authMiddleware([])         // no authentication required
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                \Webkernel\Http\Middleware\InstallerNoCacheMiddleware::class,
            ]);
    }
}
