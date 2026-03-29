<?php

declare(strict_types=1);

namespace Webkernel\Panel\Concerns;

interface RemoteComponentRegistry
{
    /**
     * Resource class-strings to inject into the given panel.
     *
     * @return list<class-string>
     */
    public function resourcesFor(string $panelId): array;

    /**
     * Page class-strings to inject into the given panel.
     *
     * @return list<class-string>
     */
    public function pagesFor(string $panelId): array;

    /**
     * Widget class-strings to inject into the given panel.
     *
     * @return list<class-string>
     */
    public function widgetsFor(string $panelId): array;
}
