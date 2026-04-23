<?php declare(strict_types=1);
namespace Webkernel\Providers;

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Filament\View\PanelsRenderHook as Hook;
use Filament\Support\Facades\FilamentIcon;
use Filament\View\PanelsIconAlias;
use Filament\Forms\View\FormsIconAlias;
use Webkernel\View\RenderHooks;
use Webkernel\QuickTouch\QuickTouch;

class FilamentRenderHooks
{
    public function boot(): void
    {
        $this->registerLayoutCss();
        $this->registerAuthPageCss();
        $this->registerIcons();
        $this->registerAuthFormHooks();

        /**
         * Add a banner to the top of the page for priviledged users.
         */
        $this->priviledgesUsersBanners();

        /*
         * QuickTouch — the entire component (render-hook + view data) is
         * owned by the QuickTouch class. The service provider only needs
         * this single line to activate everything.
         */
        QuickTouch::bootQuickTouch();
    }

    private function priviledgesUsersBanners(): void
    {
        \Filament\Support\Facades\FilamentView::registerRenderHook(
            \Filament\View\PanelsRenderHook::TOPBAR_BEFORE,
            function (): string {
                $users   = webkernel()->users();
                $current = $users->current();

                if ($current === null) {
                    return '';
                }

                $banners = [];

                if (!$users->hasOwner()) {
                    $banners[] = [
                        'id'           => 'wkt-no-owner',
                        'type'         => 'danger',
                        'title'        => 'No application owner set.',
                        'message'      => 'Assign an APP_OWNER in your environment to complete platform setup.',
                        'action_label' => 'Go to settings',
                        'action_url'   => url('filament.admin.pages.settings'),
                        'closeable'    => false,
                    ];
                }

                if ($current->isExternal()) {
                    $name  = filament()->auth()->user()->name;
                    $level = $current->getPrivilegeLevel()?->label() ?? 'External';

                    $banners[] = [
                        'id'        => 'wkt-external-' . $current->getKey(),
                        'type'      => 'warning',
                        'title'     => 'External collaborator session.',
                        'message'   => "Logged in as {$level}: {$name}. Some actions may be restricted.",
                        'closeable' => false,
                    ];
                }

                if (empty($banners)) {
                    return '';
                }

                return view('components.banner.floating-banner', [
                    'banners' => $banners,
                ])->render();
            }
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
