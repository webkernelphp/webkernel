<?php declare(strict_types=1);
namespace Webkernel;
use Filament\Facades\Filament;
use Filament\Panel as BasePanel;
use Illuminate\Support\ServiceProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
/* --- Webkernel Imports ------------*------------*------------ */
use Webkernel\Panel\Concerns\RemoteComponentRegistry;
use Webkernel\Panel\DTO\PanelDTO;
use Webkernel\Panel\Store\PanelConfigStore;

/**
 * Filament panel base provider.
 *
 * build()  — structural config only: discovery paths, pages, widgets, plugins.
 *            Do NOT set brand, layout, features, or auth here — those come from
 *            the config store and are applied by applyStoredConfig().
 *
 * $panelDefaults — declare this in subclasses to seed the panel's config file
 *                  with the right initial values on first boot. Any field from
 *                  PanelDTO::schema() is valid here.
 *
 * Octane safe: no static state. Store reads hit Laravel's external cache.
 */
abstract class PanelProvider extends ServiceProvider
{
    protected string $panelId   = '';
    protected string $panelPath = '';

    protected bool $withEncryptCookies = true;
    protected bool $withSession        = true;
    protected bool $withCsrf           = true;
    protected bool $withBindings       = true;
    protected bool $withBladeIcons     = true;
    protected bool $withFilamentEvents = true;

    protected array $extraMiddleware     = [];
    protected array $extraAuthMiddleware = [];

    protected bool $acceptRemoteComponents = false;

    /**
     * Initial values written to the panel config file on first boot.
     * Override in subclasses to set panel-specific defaults.
     * Any key from PanelDTO::schema() is valid.
     *
     * @var array<string, mixed>
     */
    protected array $panelDefaults = [];

    /**
     * Structural config only — discovery, pages, widgets, plugins.
     * Brand, layout, features, and auth are managed via the config store.
     */
    abstract protected function build(Panel $panel): Panel;

    public function register(): void
    {
        Filament::registerPanel(fn (): BasePanel => $this->assemble());
    }

    public function boot(): void
    {
        if ($this->acceptRemoteComponents) {
            $this->bootRemoteComponents();
        }
    }

    // -------------------------------------------------------------------------
    // Assembly
    // -------------------------------------------------------------------------

    private function assemble(): BasePanel
    {
        $panel = BasePanel::make()
            ->id($this->resolvePanelId())
            ->path($this->resolvePanelPath())
            ->middleware($this->buildMiddleware())
            ->authMiddleware($this->buildAuthMiddleware());

        $built = $this->build(new Panel($panel))->toBase();

        $store = $this->app->make(PanelConfigStore::class);

        if ($store->find($built->getId()) === null) {
            $store->save(
                $built->getId(),
                array_merge(
                    PanelDTO::defaults($built->getId())->toArray(),
                    $this->panelDefaults,
                ),
            );
        }

        return $this->applyStoredConfig($built, $store);
    }

    /**
     * Apply the full stored PanelDTO config on top of what build() set.
     * Stored values always win over build() — that is the contract.
     */
    private function applyStoredConfig(BasePanel $panel, PanelConfigStore $store): BasePanel
    {
        $raw = $store->find($panel->getId());

        if (empty($raw)) {
            return $panel;
        }

        $dto = PanelDTO::fromArray(array_merge($raw, ['id' => $panel->getId()]));

        // Brand
        if ($dto->brandLogo)       $panel->brandLogo($dto->brandLogo);
        if ($dto->brandLogoDark)   $panel->darkModeBrandLogo($dto->brandLogoDark);
        if ($dto->brandLogoHeight) $panel->brandLogoHeight($dto->brandLogoHeight);
        if ($dto->brandName)       $panel->brandName($dto->brandName);
        if ($dto->favicon)         $panel->favicon($dto->favicon);
        if ($dto->primaryColor) {
            $color = str_contains($dto->primaryColor, '::')
                ? constant($dto->primaryColor)
                : $dto->primaryColor;
            $panel->colors(['primary' => $color]);
        }

        // Layout
        if ($dto->maxContentWidth)       $panel->maxContentWidth($dto->maxContentWidth);
        if ($dto->sidebarWidth)          $panel->sidebarWidth($dto->sidebarWidth);
        if ($dto->collapsedSidebarWidth) $panel->collapsedSidebarWidth($dto->collapsedSidebarWidth);
        $panel->sidebarCollapsibleOnDesktop($dto->sidebarCollapsible);
        $panel->sidebarFullyCollapsibleOnDesktop($dto->sidebarFullyCollapsible);
        $panel->topNavigation($dto->topNavigation);
        $panel->topbar($dto->hasTopbar);

        // Features
        $panel->spa($dto->spa);
        $panel->darkMode($dto->darkMode);
        $panel->globalSearch($dto->globalSearch);
        $panel->broadcasting($dto->broadcasting);
        $panel->databaseNotifications($dto->databaseNotifications);
        $panel->unsavedChangesAlerts($dto->unsavedChangesAlerts);
        $panel->databaseTransactions($dto->databaseTransactions);

        // Auth — only enable, never disable (Filament has no disable methods)
        if ($dto->auth)              $panel->login();
        if ($dto->registration)      $panel->registration();
        if ($dto->passwordReset)     $panel->passwordReset();
        if ($dto->emailVerification) $panel->emailVerification();
        if ($dto->mfaRequired)       $panel->requiresMultiFactorAuthentication();
        if ($dto->authGuard)         $panel->authGuard($dto->authGuard);
        if ($dto->authPasswordBroker) $panel->authPasswordBroker($dto->authPasswordBroker);

        // Routing
        if ($dto->homeUrl)         $panel->homeUrl($dto->homeUrl);
        if (! empty($dto->domains)) $panel->domains($dto->domains);

        return $panel;
    }

    // -------------------------------------------------------------------------
    // Identity
    // -------------------------------------------------------------------------

    protected function resolvePanelId(): string
    {
        if ($this->panelId === '') {
            throw new \RuntimeException(static::class . ' must define $panelId.');
        }

        return $this->panelId;
    }

    protected function resolvePanelPath(): string
    {
        return $this->panelPath;
    }

    // -------------------------------------------------------------------------
    // Middleware
    // -------------------------------------------------------------------------

    protected function buildMiddleware(): array
    {
        $stack = [];

        if ($this->withEncryptCookies) {
            $stack[] = EncryptCookies::class;
            $stack[] = AddQueuedCookiesToResponse::class;
        }

        if ($this->withSession) {
            $stack[] = StartSession::class;
            $stack[] = ShareErrorsFromSession::class;
        }

        if ($this->withCsrf)           $stack[] = PreventRequestForgery::class;
        if ($this->withBindings)       $stack[] = SubstituteBindings::class;
        if ($this->withBladeIcons)     $stack[] = DisableBladeIconComponents::class;
        if ($this->withFilamentEvents) $stack[] = DispatchServingFilamentEvent::class;

        return $this->extraMiddleware
            ? [...$stack, ...$this->extraMiddleware]
            : $stack;
    }

    protected function buildAuthMiddleware(): array
    {
        $base = [Authenticate::class];

        if ($this->withSession) {
            $base[] = AuthenticateSession::class;
        }

        return $this->extraAuthMiddleware
            ? [...$base, ...$this->extraAuthMiddleware]
            : $base;
    }

    // -------------------------------------------------------------------------
    // Remote components
    // -------------------------------------------------------------------------

    private function bootRemoteComponents(): void
    {
        if (! $this->app->bound(RemoteComponentRegistry::class)) {
            return;
        }

        $registry = $this->app->make(RemoteComponentRegistry::class);
        $panelId  = $this->resolvePanelId();

        foreach ($registry->resourcesFor($panelId) as $resource) {
            Filament::registerResources([$resource]);
        }

        foreach ($registry->pagesFor($panelId) as $page) {
            Filament::registerPages([$page]);
        }

        foreach ($registry->widgetsFor($panelId) as $widget) {
            Filament::registerWidgets([$widget]);
        }
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    final public function isOpenToRemoteComponents(): bool
    {
        return $this->acceptRemoteComponents;
    }

    final public function getPanelId(): string
    {
        return $this->resolvePanelId();
    }
}
