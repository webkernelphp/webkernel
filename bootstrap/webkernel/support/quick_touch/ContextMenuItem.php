<?php declare(strict_types=1);

namespace Webkernel\QuickTouch;

/**
 * A single item (or divider) in the QuickTouch right-click / footer context menu.
 *
 * Usage:
 *
 *   ContextMenuRegistry::register(
 *       ContextMenuItem::make('copy-url')
 *           ->label('Copy URL')
 *           ->icon('<svg …/>')
 *           ->onClick("navigator.clipboard.writeText(window.location.href)")
 *   );
 *
 *   // Divider:
 *   ContextMenuRegistry::register(ContextMenuItem::divider());
 */
class ContextMenuItem
{
    private string  $name;
    private string  $label    = '';
    private string  $icon     = '';
    private string  $onClick  = '';
    private string  $url      = '';
    private bool    $newTab   = false;
    private bool    $isDivider = false;
    private int     $sort     = 100;

    private function __construct(string $name)
    {
        $this->name = $name;
    }

    public static function make(string $name): static
    {
        return new static($name);
    }

    public static function divider(): static
    {
        $item = new static('__divider__' . uniqid());
        $item->isDivider = true;
        return $item;
    }

    // ── fluent ───────────────────────────────────────────────────────────────

    public function label(string $label): static  { $this->label   = $label;  return $this; }
    public function icon(string $svg): static     { $this->icon    = $svg;    return $this; }
    public function onClick(string $js): static   { $this->onClick = $js;     return $this; }
    public function sort(int $sort): static       { $this->sort    = $sort;   return $this; }

    public function url(string $url, bool $newTab = false): static
    {
        $this->url    = $url;
        $this->newTab = $newTab;
        return $this;
    }

    // ── getters ──────────────────────────────────────────────────────────────

    public function getName(): string    { return $this->name; }
    public function getLabel(): string   { return $this->label; }
    public function getIcon(): string    { return $this->icon; }
    public function getOnClick(): string { return $this->onClick; }
    public function getUrl(): string     { return $this->url; }
    public function isNewTab(): bool     { return $this->newTab; }
    public function isDivider(): bool    { return $this->isDivider; }
    public function getSort(): int       { return $this->sort; }

    public function toArray(): array
    {
        return [
            'name'      => $this->name,
            'label'     => $this->label,
            'icon'      => $this->icon,
            'onClick'   => $this->onClick,
            'url'       => $this->url,
            'newTab'    => $this->newTab,
            'isDivider' => $this->isDivider,
            'sort'      => $this->sort,
        ];
    }
}
