<?php declare(strict_types=1);

namespace Webkernel;

use Filament\Panel as FilamentPanel;

/**
 * Thin wrapper over Filament\Panel.
 * - Stateless
 * - No dynamic properties
 * - Full passthrough via __call
 * - No extra allocations except wrapper itself
 */
final class Panel
{
    private FilamentPanel $panel;

    public function __construct(FilamentPanel $panel)
    {
        $this->panel = $panel;
    }

    /**
     * Forward all calls to Filament Panel.
     * Returns self when Filament returns itself to preserve chaining.
     */
    public function __call(string $method, array $arguments): mixed
    {
        $result = $this->panel->{$method}(...$arguments);

        return $result instanceof FilamentPanel ? $this : $result;
    }

    /**
     * Unwrap underlying Filament panel.
     */
    public function toBase(): FilamentPanel
    {
        return $this->panel;
    }
}
