<?php

declare(strict_types=1);

namespace Webkernel\Panel\Support;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel as FilamentPanel;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Webkernel\Panel\DTO\PanelDTO;

/**
 * PanelRuntimeFactory
 *
 * Transforms a PanelDTO into a fully configured Filament\Panel instance.
 *
 * Contract:
 *   - No DB queries
 *   - No static mutable state (Octane safe)
 *   - No Reflection at runtime (PanelSchemaInspector is only used at build time)
 *   - Every Filament\Panel method call is driven by the DTO — nothing hardcoded
 *
 * Usage:
 *   $factory = new PanelRuntimeFactory();
 *   $panel   = $factory->make($dto);
 *   Filament::registerPanel($panel);
 *
 * Or via the static shorthand:
 *   $panel = PanelRuntimeFactory::build($dto);
 */
final class PanelRuntimeFactory
{
    public function make(PanelDTO $dto): FilamentPanel
    {
        $panel = FilamentPanel::make()
            ->id($dto->id)
            ->path($dto->path);

        $this->applyIdentity($panel, $dto);
        $this->applyBrand($panel, $dto);
        $this->applyLayout($panel, $dto);
        $this->applyFeatures($panel, $dto);
        $this->applyAuth($panel, $dto);
        $this->applyTenant($panel, $dto);
        $this->applyComponents($panel, $dto);
        $this->applyDiscovery($panel, $dto);
        $this->applyMiddleware($panel, $dto);

        return $panel;
    }

    public static function build(PanelDTO $dto): FilamentPanel
    {
        return (new self())->make($dto);
    }

    // -------------------------------------------------------------------------
    // Private appliers — one concern per method, order matters
    // -------------------------------------------------------------------------

    private function applyIdentity(FilamentPanel $panel, PanelDTO $dto): void
    {
        if ($dto->isDefault) {
            $panel->default();
        }

        if ($dto->homeUrl !== '') {
            $panel->homeUrl($dto->homeUrl);
        }

        if ($dto->domains !== []) {
            $panel->domains($dto->domains);
        }
    }

    private function applyBrand(FilamentPanel $panel, PanelDTO $dto): void
    {
        if ($dto->brandName !== '') {
            $panel->brandName($dto->brandName);
        }

        if ($dto->brandLogo !== '') {
            $panel->brandLogo($dto->brandLogo);
        }

        if ($dto->brandLogoDark !== '') {
            $panel->darkModeBrandLogo($dto->brandLogoDark);
        }

        if ($dto->brandLogoHeight !== '') {
            $panel->brandLogoHeight($dto->brandLogoHeight);
        }

        if ($dto->favicon !== '') {
            $panel->favicon($dto->favicon);
        }

        if ($dto->primaryColor !== '') {
            $panel->colors(['primary' => Color::hex($dto->primaryColor)]);
        }
    }

    private function applyLayout(FilamentPanel $panel, PanelDTO $dto): void
    {
        if ($dto->sidebarWidth !== '20rem') {
            $panel->sidebarWidth($dto->sidebarWidth);
        }

        if ($dto->collapsedSidebarWidth !== '4.5rem') {
            $panel->collapsedSidebarWidth($dto->collapsedSidebarWidth);
        }

        if ($dto->sidebarCollapsible) {
            $panel->sidebarCollapsibleOnDesktop();
        }

        if ($dto->sidebarFullyCollapsible) {
            $panel->sidebarFullyCollapsibleOnDesktop();
        }

        if ($dto->topNavigation) {
            $panel->topNavigation();
        }

        if (! $dto->hasTopbar) {
            $panel->topbar(false);
        }

        if ($dto->maxContentWidth !== '') {
            $panel->maxContentWidth($dto->maxContentWidth);
        }
    }

    private function applyFeatures(FilamentPanel $panel, PanelDTO $dto): void
    {
        if ($dto->spa) {
            $panel->spa();
        }

        if (! $dto->darkMode) {
            $panel->darkMode(false);
        }

        if ($dto->darkModeForced) {
            $panel->darkMode(true);
        }

        if (! $dto->globalSearch) {
            $panel->globalSearch(false);
        }

        if ($dto->broadcasting) {
            $panel->broadcasting();
        }

        if ($dto->databaseNotifications) {
            $panel->databaseNotifications();
        }

        if ($dto->unsavedChangesAlerts) {
            $panel->unsavedChangesAlerts();
        }

        if ($dto->databaseTransactions) {
            $panel->databaseTransactions();
        }
    }

    private function applyAuth(FilamentPanel $panel, PanelDTO $dto): void
    {
        if ($dto->registration) {
            $panel->registration();
        }

        if ($dto->passwordReset) {
            $panel->passwordReset();
        }

        if ($dto->emailVerification) {
            $panel->emailVerification();
        }

        if ($dto->authGuard !== 'web' && $dto->authGuard !== '') {
            $panel->authGuard($dto->authGuard);
        }

        if ($dto->authPasswordBroker !== '') {
            $panel->authPasswordBroker($dto->authPasswordBroker);
        }
    }

    private function applyTenant(FilamentPanel $panel, PanelDTO $dto): void
    {
        if (! $dto->tenantEnabled) {
            return;
        }

        if ($dto->tenantModel !== '' && class_exists($dto->tenantModel)) {
            $panel->tenant($dto->tenantModel);
        }

        if ($dto->tenantDomain !== '') {
            $panel->tenantDomain($dto->tenantDomain);
        }

        if ($dto->tenantOwnershipRelation !== '') {
            $panel->tenantOwnershipRelationshipName($dto->tenantOwnershipRelation);
        }
    }

    private function applyComponents(FilamentPanel $panel, PanelDTO $dto): void
    {
        $resources = array_filter($dto->resources, 'class_exists');
        $pages     = array_filter($dto->pages, 'class_exists');
        $widgets   = array_filter($dto->widgets, 'class_exists');

        if ($resources !== []) {
            $panel->resources($resources);
        }

        if ($pages !== []) {
            $panel->pages($pages);
        }

        if ($widgets !== []) {
            $panel->widgets($widgets);
        }
    }

    private function applyDiscovery(FilamentPanel $panel, PanelDTO $dto): void
    {
        if ($dto->discoverResourcesIn !== '' && $dto->discoverResourcesFor !== '') {
            $panel->discoverResources(
                in: $dto->discoverResourcesIn,
                for: $dto->discoverResourcesFor,
            );
        }

        if ($dto->discoverPagesIn !== '' && $dto->discoverPagesFor !== '') {
            $panel->discoverPages(
                in: $dto->discoverPagesIn,
                for: $dto->discoverPagesFor,
            );
        }

        if ($dto->discoverWidgetsIn !== '' && $dto->discoverWidgetsFor !== '') {
            $panel->discoverWidgets(
                in: $dto->discoverWidgetsIn,
                for: $dto->discoverWidgetsFor,
            );
        }
    }

    private function applyMiddleware(FilamentPanel $panel, PanelDTO $dto): void
    {
        $stack = [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            AuthenticateSession::class,
            ShareErrorsFromSession::class,
            PreventRequestForgery::class,
            SubstituteBindings::class,
            DisableBladeIconComponents::class,
            DispatchServingFilamentEvent::class,
            ...$dto->extraMiddleware,
        ];

        $panel->middleware($stack);

        if ($dto->auth) {
            $authStack = [Authenticate::class, ...$dto->extraAuthMiddleware];
            $panel->authMiddleware($authStack);
        } elseif ($dto->extraAuthMiddleware !== []) {
            $panel->authMiddleware($dto->extraAuthMiddleware);
        }
    }
}
