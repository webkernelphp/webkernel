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
                    ->url(fn () => WebkernelSettingResource::getUrl('index')),
                NavigationItem::make('Logging')
                    ->icon('document-text'),
                NavigationItem::make('Security')
                    ->icon('lock-closed'),
                NavigationItem::make('Users & Access')
                    ->icon('users'),
                NavigationItem::make('Storage')
                    ->icon('document'),
                NavigationItem::make('Database')
                    ->icon('server'),
                NavigationItem::make('Performance')
                    ->icon('chart-bar'),
                NavigationItem::make('Modules')
                    ->icon('puzzle'),
                NavigationItem::make('Integrations')
                    ->icon('link'),
                NavigationItem::make('Composer')
                    ->icon('code-bracket')
                    ->url(fn () => DependencyManagerPage::getUrl()),
                NavigationItem::make('NPM')
                    ->icon('code-bracket')
                    ->url(fn () => NpmDependencyManagerPage::getUrl()),
                NavigationItem::make('Background Tasks')
                    ->icon('play')
                    ->url(fn () => BackgroundTasksResource::getUrl('index')),
                NavigationItem::make('Monitoring')
                    ->icon('signal'),
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


            ->pages([
                Dashboard::class,
            ])

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
