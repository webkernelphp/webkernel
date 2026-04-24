<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Providers;

use Filament\Actions\Action;
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
use Webkernel\BackOffice\System\Presentation\Resources\WebkernelSettings\WebkernelSettingResource;
use Filament\Support\Enums\Width;
use Filament\Support\Colors\Color;
use Filament\Navigation\NavigationItem;

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
            ->navigationItems([
                NavigationItem::make('Settings')
                    ->icon('cog')
                    ->url(fn () => WebkernelSettingResource::getUrl('index'))
                    ->group('System'),
                NavigationItem::make('Logging')
                    ->icon('document-text')
                    ->group('System'),
                NavigationItem::make('Security')
                    ->icon('lock-closed')
                    ->group('System'),
                NavigationItem::make('Users & Access')
                    ->icon('users')
                    ->group('Infrastructure'),
                NavigationItem::make('Storage')
                    ->icon('document')
                    ->group('Infrastructure'),
                NavigationItem::make('Database')
                    ->icon('server')
                    ->group('Infrastructure'),
                NavigationItem::make('Performance')
                    ->icon('chart-bar')
                    ->group('Infrastructure'),
                NavigationItem::make('Modules')
                    ->icon('puzzle')
                    ->group('Marketplace'),
                NavigationItem::make('Integrations')
                    ->icon('link')
                    ->group('Marketplace'),
                NavigationItem::make('Composer')
                    ->icon('code-bracket')
                    ->url(fn () => DependencyManagerPage::getUrl())
                    ->group('Maintenance'),
                NavigationItem::make('NPM')
                    ->icon('code-bracket')
                    ->url(fn () => NpmDependencyManagerPage::getUrl())
                    ->group('Maintenance'),
                NavigationItem::make('Background Tasks')
                    ->icon('play')
                    ->url(fn () => BackgroundTasksResource::getUrl('index'))
                    ->group('Maintenance'),
                NavigationItem::make('Monitoring')
                    ->icon('signal')
                    ->group('Maintenance'),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('screen-xxl')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->login()
            ->databaseNotifications()
            ->databaseNotificationsPolling('3s')
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

            //->userActions([
            //    Action::make('user-background-tasks')
            //        ->label('Background Tasks')
            //        ->icon('play')
            //        ->badge(fn () => WebkernelBackgroundTask::active()->count())
            //        ->url(fn () => BackgroundTasksResource::getUrl())
            //        ->visible(fn () => WebkernelBackgroundTask::active()->count() > 0),
            //])
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
