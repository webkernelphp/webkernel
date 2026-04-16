<?php declare(strict_types=1);

namespace Webkernel\QuickTouch;

/**
 * Represents a single action shown in the QuickTouch quick-grid or action list.
 *
 * Usage (in a service provider or boot callback):
 *
 *   ActionRegistry::register(
 *       QuickTouchAction::make('refresh')
 *           ->label('Refresh')
 *           ->icon('<svg …/>')   // raw inline SVG string
 *           ->onClick('window.location.reload()')
 *   );
 */
class QuickTouchAction
{
    private string $name;
    private string $label       = '';
    private string $icon        = '';
    private string $onClick     = '';
    private string $url         = '';
    private bool   $newTab      = false;
    private ?string $resource   = null;   // null = global
    private ?string $scope      = null;   // 'row' | 'table' | 'widget' | null

    private function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function make(string $name): static
    {
        return new static($name);
    }

    // ── fluent setters ───────────────────────────────────────────────────────

    public function label(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    /** Raw inline SVG string (no external dependency). */
    public function icon(string $svg): static
    {
        $this->icon = $svg;
        return $this;
    }

    /** Inline JS expression evaluated on click (e.g. "window.location.reload()"). */
    public function onClick(string $js): static
    {
        $this->onClick = $js;
        return $this;
    }

    public function url(string $url, bool $newTab = false): static
    {
        $this->url    = $url;
        $this->newTab = $newTab;
        return $this;
    }

    /** Scope to a specific Filament resource FQCN. */
    public function forResource(string $resource): static
    {
        $this->resource = $resource;
        return $this;
    }

    /**
     * Scope: where the action appears.
     *
     * @param  'row'|'table'|'widget'|null  $scope
     */
    public function scope(?string $scope): static
    {
        $this->scope = $scope;
        return $this;
    }

    // ── getters ──────────────────────────────────────────────────────────────

    public function getName(): string    { return $this->name; }
    public function getLabel(): string   { return $this->label; }
    public function getIcon(): string    { return $this->icon; }
    public function getOnClick(): string { return $this->onClick; }
    public function getUrl(): string     { return $this->url; }
    public function isNewTab(): bool     { return $this->newTab; }
    public function getResource(): ?string { return $this->resource; }
    public function getScope(): ?string    { return $this->scope; }

    public function isGlobal(): bool
    {
        return $this->resource === null && $this->scope === null;
    }

    /** Serialise to array for JSON / Blade. */
    public function toArray(): array
    {
        return [
            'name'     => $this->name,
            'label'    => $this->label,
            'icon'     => $this->icon,
            'onClick'  => $this->onClick,
            'url'      => $this->url,
            'newTab'   => $this->newTab,
            'resource' => $this->resource,
            'scope'    => $this->scope,
        ];
    }
}
