<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Providers;

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
use Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Pages\DependencyManagerPage;
use Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Pages\NpmDependencyManagerPage;
use Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\FilamentDependencyManagerServiceProvider;
use Webkernel\BackOffice\System\Models\WebkernelBackgroundTask;
use Webkernel\BackOffice\System\Presentation\Resources\BackgroundTasks\BackgroundTasksResource;
use Filament\Support\Enums\Width;
use Filament\Support\Colors\Color;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\MenuItem;

final class SystemPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        $this->app->register(FilamentDependencyManagerServiceProvider::class);
    }

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
            ->topbar()
            ->spa()
            ->globalSearch()
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('screen-xxl')
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('System')
                    ->icon('layout-dashboard'),

                NavigationGroup::make()
                    ->label('Infrastructure')
                    ->icon('database')
                    ->collapsed(),

                NavigationGroup::make()
                    ->label('Marketplace')
                    ->icon('shopping-bag')
                    ->collapsed(),

                NavigationGroup::make()
                    ->label('Maintenance')
                    ->icon('wrench')
                    ->collapsed(),
            ])
            ->colors([
                'primary' => Color::Blue,
            ])
            ->login()
            ->profile(isSimple: false)
            ->discoverResources(
                in: __DIR__ . '/../Presentation/Resources',
                for: 'Webkernel\BackOffice\System\Presentation\Resources',
            )
            ->discoverPages(
                in: __DIR__ . '/../Presentation/Pages',
                for: 'Webkernel\BackOffice\System\Presentation\Pages',
            )
            ->pages([
                Dashboard::class,
                DependencyManagerPage::class,
                NpmDependencyManagerPage::class,
            ])
            ->discoverWidgets(
                in: __DIR__ . '/../Presentation/Widgets',
                for: 'Webkernel\BackOffice\System\Presentation\Widgets',
            )
            ->widgets([AccountWidget::class, FilamentInfoWidget::class])
            ->userMenuItems([
                MenuItem::make()
                    ->label(fn () => WebkernelBackgroundTask::active()->count() . ' background task(s) running')
                    ->icon('heroicon-o-clock')
                    ->url(fn () => BackgroundTasksResource::getUrl())
                    ->visible(fn () => WebkernelBackgroundTask::active()->count() > 0),
            ])
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
