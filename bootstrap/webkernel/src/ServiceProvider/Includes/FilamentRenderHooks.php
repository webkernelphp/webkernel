<?php declare(strict_types=1);
namespace Webkernel\ServiceProvider\Includes;

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Filament\View\PanelsRenderHook as Hook;
use Filament\Support\Facades\FilamentIcon;
use Filament\View\PanelsIconAlias;
use Filament\Forms\View\FormsIconAlias;
use Webkernel\View\RenderHooks;

class FilamentRenderHooks
{
    public function boot(): void
    {
        $this->registerLayoutCss();
        $this->registerVersionBadge();
        $this->registerAuthPageCss();
        $this->registerIcons();
        $this->registerAuthFormHooks();
    }

    // ── Layout CSS (BODY_START, Octane-safe) ─────────────────────────────────
    private function registerLayoutCss(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_START,
            function (): string {
                return view('webkernel::panels.layout.css', [
                    'sidebarKeepsBackground' => filament()->isSidebarCollapsibleOnDesktop(),
                ])->render();
            },
        );
    }

    // ── Version badge ─────────────────────────────────────────────────────────
    private function registerVersionBadge(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::USER_MENU_PROFILE_BEFORE,
            function (): string {
                return '<span class="fi-filament-info-widget-version">'
                    . WEBKERNEL_CODENAME . ' '
                    . WEBKERNEL_CHANNEL  . ' v'
                    . WEBKERNEL_VERSION  . '</span>';
            },
        );
    }

    // ── Auth page CSS ─────────────────────────────────────────────────────────
    //
    // Background images are injected via two scoped render hooks so that any
    // webkernel application can override them cleanly:
    //
    //   FilamentView::registerRenderHook(
    //       \Webkernel\View\WebkernelRenderHook::AUTH_BG_LIGHT,
    //       fn () => 'https://example.com/my-light-bg.jpg',
    //   );
    //
    // If nobody registers a hook the built-in defaults are used.
    private function registerAuthPageCss(): void
    {
        $authPaths = ['login', 'register', 'password-reset', 'profile'];

        if (!str(request()->path())->contains($authPaths)) {
            return;
        }

        foreach ([
            PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
            PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
            PanelsRenderHook::AUTH_PASSWORD_RESET_REQUEST_FORM_AFTER,
            PanelsRenderHook::AUTH_PASSWORD_RESET_REQUEST_FORM_BEFORE,
            PanelsRenderHook::AUTH_PASSWORD_RESET_RESET_FORM_AFTER,
            PanelsRenderHook::AUTH_PASSWORD_RESET_RESET_FORM_BEFORE,
            PanelsRenderHook::AUTH_REGISTER_FORM_AFTER,
            PanelsRenderHook::AUTH_REGISTER_FORM_BEFORE,
        ] as $hook) {
            FilamentView::registerRenderHook(
                $hook,
                function (): string {
                    $bgLight = (string) FilamentView::renderHook(RenderHooks::AUTH_BG_LIGHT);
                    $bgDark  = (string) FilamentView::renderHook(RenderHooks::AUTH_BG_DARK);

                    if (trim($bgLight) === '') {
                        $bgLight = asset('webkernel/auth/classic-tree-light-login.png');
                    }
                    if (trim($bgDark) === '') {
                        $bgDark = asset('webkernel/auth/classic-tree-dark-login.png');
                    }

                    return view('webkernel::panels.auth.css', [
                        'bgLight' => $bgLight,
                        'bgDark'  => $bgDark,
                    ])->render();
                },
            );
        }
    }

    // ── Icon aliases ──────────────────────────────────────────────────────────
    private function registerIcons(): void
    {
        if (!class_exists(FilamentIcon::class)) {
            return;
        }

        FilamentIcon::register([
            PanelsIconAlias::GLOBAL_SEARCH_FIELD                       => 'search',
            PanelsIconAlias::TOPBAR_OPEN_DATABASE_NOTIFICATIONS_BUTTON => 'bell',
            FormsIconAlias::COMPONENTS_REPEATER_ACTIONS_CLONE          => 'clipboard',
            FormsIconAlias::COMPONENTS_REPEATER_ACTIONS_DELETE         => 'trash-2',
            PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON                   => 'panel-right-open',
            PanelsIconAlias::SIDEBAR_EXPAND_BUTTON                     => 'panel-left-open',
        ]);
    }

    // ── Theme-switcher after auth forms ───────────────────────────────────────
    private function registerAuthFormHooks(): void
    {
        collect([
            Hook::AUTH_LOGIN_FORM_AFTER,
            Hook::AUTH_REGISTER_FORM_AFTER,
            Hook::AUTH_PASSWORD_RESET_RESET_FORM_AFTER,
            Hook::AUTH_PASSWORD_RESET_REQUEST_FORM_AFTER,
        ])->each(fn ($hook) => FilamentView::registerRenderHook(
            $hook,
            fn () => static::renderAuthRegisterFormAfter(),
        ));
    }

    public static function renderAuthRegisterFormAfter(): string
    {
        return '<div style="margin-top: 1rem; padding: 0 1rem; max-width: 384px; margin-inline: auto;">'
            . view('filament-panels::components.theme-switcher.index')->render()
            . '</div>';
    }
}
