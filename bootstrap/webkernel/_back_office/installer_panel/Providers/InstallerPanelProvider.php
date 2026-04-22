<?php declare(strict_types=1);

namespace Webkernel\BackOffice\Installer\Providers;

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
use Webkernel\BackOffice\Installer\Presentation\Installer\InstallerPage;
use Filament\Support\Enums\Width;
use Filament\Support\Colors\Color;

use Illuminate\Http\Request;
use Closure;

/**
 * Forces no-cache headers on all installer panel responses.
 *
 * Prevents the browser from caching the installer HTML page, which contains
 * the Livewire script URL (hashed from APP_KEY). Without this, a browser
 * that cached the page with an old APP_KEY would send Livewire requests to
 * a non-existent route (hash mismatch → 404).
 */
final class InstallerNoCacheMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if (method_exists($response, 'header')) {
            $response->header('Cache-Control', 'no-store, no-cache, must-revalidate');
            $response->header('Pragma', 'no-cache');
        }

        return $response;
    }
}

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
                InstallerNoCacheMiddleware::class,
            ]);
    }
}
