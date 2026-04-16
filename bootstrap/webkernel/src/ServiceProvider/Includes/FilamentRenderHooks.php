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
        $this->registerWebkernelTouch();
    }

    /**
     * Inject Webkernel Touch into all registered Filament panels.
     *
     * The component is rendered once before the closing body tag so it sits
     * above all panel content without interfering with any layout hooks.
     *
     * The window.wktPanels array is populated here with every registered panel
     * so the switcher tab inside the component can list them without any
     * server round-trip.
     */
    private function registerWebkernelTouch(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            static function (): \Illuminate\Contracts\View\View {
                $panels = collect(\Filament\Facades\Filament::getPanels())
                    ->map(fn ($panel) => [
                        'label' => $panel->getId(),
                        'url'   => $panel->getUrl() ?? '/',
                    ])
                    ->values()
                    ->toArray();


                    $wktEnabled = true;

                    $wktPanels = $panels;

                    $wktUser = [
                        'name'  => 'Demo User',
                        'email' => 'demo@webkernel.dev',
                    ];

                    /*
                     * Pre-seeded favorites — will be written to localStorage on first load
                     * so the Main tab is never empty in demo mode.
                     */
                    $wktFavorites = [
                        ['url' => '/admin/users',    'title' => 'Users'],
                        ['url' => '/admin/settings', 'title' => 'Settings'],
                        ['url' => '/admin/logs',     'title' => 'Logs'],
                    ];
                return view('webkernel-touch', [
                    'panels' => $panels,
                    'wktPanelsJson'    => json_encode($wktPanels),
                    'wktFavoritesJson' => json_encode($wktFavorites),
                    'wktUser' => json_encode($wktUser),
                ]);
            },
        );
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
    // Injected via STYLES_AFTER (inside <head>, outside any Livewire component)
    // so Livewire AJAX morphing never removes it — validation errors, retries,
    // and any component update keep the background images intact.
    //
    // Background image URLs can be overridden per-app:
    //   FilamentView::registerRenderHook(
    //       \Webkernel\View\WebkernelRenderHook::AUTH_BG_LIGHT,
    //       fn () => 'https://example.com/my-bg.jpg',
    //   );
    private function registerAuthPageCss(): void
    {
        $authPaths = ['login', 'register', 'password-reset'];

        if (!str(request()->path())->contains($authPaths)) {
            return;
        }

        FilamentView::registerRenderHook(
            PanelsRenderHook::STYLES_AFTER,
            function (): string {
                $bgLight = (string) FilamentView::renderHook(RenderHooks::AUTH_BG_LIGHT);
                $bgDark  = (string) FilamentView::renderHook(RenderHooks::AUTH_BG_DARK);

                if (trim($bgLight) === '') {
                    $bgLight = webkernelBrandingUrl('webkernel-bg-login-light');
                }
                if (trim($bgDark) === '') {
                    $bgDark = webkernelBrandingUrl('webkernel-bg-login-dark');
                }

                return view('webkernel::panels.auth.css', [
                    'bgLight' => $bgLight,
                    'bgDark'  => $bgDark,
                ])->render();
            },
        );
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
