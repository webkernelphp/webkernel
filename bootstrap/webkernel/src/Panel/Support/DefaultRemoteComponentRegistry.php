<?php

declare(strict_types=1);

namespace Webkernel\Panel\Support;

use Webkernel\Panel\Concerns\RemoteComponentRegistry;

/**
 * Default in-memory registry.
 *
 * Bound as singleton in WebkernelServiceProvider:
 *   $this->app->singleton(RemoteComponentRegistry::class, DefaultRemoteComponentRegistry::class);
 *
 * Module service providers inject into it:
 *
 *   use Webkernel\Contracts\RemoteComponentRegistry;
 *
 *   public function boot(): void
 *   {
 *       $this->callAfterResolving(RemoteComponentRegistry::class,
 *           function (RemoteComponentRegistry $registry): void {
 *               $registry->addResource('admin', MyModuleResource::class);
 *               $registry->addPage('admin', MyModulePage::class);
 *               $registry->addWidget('admin', MyModuleWidget::class);
 *           }
 *       );
 *   }
 */
final class DefaultRemoteComponentRegistry implements RemoteComponentRegistry
{
    /** @var array<string, list<class-string>> */
    private array $resources = [];

    /** @var array<string, list<class-string>> */
    private array $pages = [];

    /** @var array<string, list<class-string>> */
    private array $widgets = [];

    // -------------------------------------------------------------------------
    // Registration — called by module service providers
    // -------------------------------------------------------------------------

    /** @param class-string $resource */
    public function addResource(string $panelId, string $resource): void
    {
        $this->resources[$panelId][] = $resource;
    }

    /** @param class-string $page */
    public function addPage(string $panelId, string $page): void
    {
        $this->pages[$panelId][] = $page;
    }

    /** @param class-string $widget */
    public function addWidget(string $panelId, string $widget): void
    {
        $this->widgets[$panelId][] = $widget;
    }

    // -------------------------------------------------------------------------
    // RemoteComponentRegistry
    // -------------------------------------------------------------------------

    public function resourcesFor(string $panelId): array
    {
        return $this->resources[$panelId] ?? [];
    }

    public function pagesFor(string $panelId): array
    {
        return $this->pages[$panelId] ?? [];
    }

    public function widgetsFor(string $panelId): array
    {
        return $this->widgets[$panelId] ?? [];
    }
}
