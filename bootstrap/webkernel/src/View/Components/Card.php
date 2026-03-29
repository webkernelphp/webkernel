<?php declare(strict_types=1);

namespace Webkernel\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Card
 *
 * Composable structured surface with optional leading, meta, and footer regions.
 * This is a structural primitive — no business logic, no domain knowledge.
 *
 * Layout:
 *   [ leading ] [ header / content ] [ meta ]
 *                    [ footer ]
 *
 * Usage:
 *   <x-webkernel::card compact interactive tone="emerald">
 *       <x-slot name="leading"> ... </x-slot>
 *       <x-slot name="header">  ... </x-slot>
 *       <x-slot name="content"> ... </x-slot>
 *       <x-slot name="meta">    ... </x-slot>
 *       <x-slot name="footer">  ... </x-slot>
 *   </x-webkernel::card>
 */
class Card extends Component
{
    /**
     * @param  bool        $compact      Reduces padding and spacing
     * @param  bool        $interactive  Adds hover elevation and pointer cursor
     * @param  bool        $disabled     Mutes the entire card
     * @param  string|null $variant      Stylistic variant: 'metric' | 'status' | 'flat' | null
     * @param  string|null $tone         Color tone: 'emerald' | 'amber' | 'red' | 'blue' | 'gray' | null
     * @param  string|null $href         When set, wraps the card in an anchor tag (fully clickable)
     * @param  string|null $target       Link target (_blank etc.) — only used when href is set
     * @param  bool        $clickable    Adds interactive styling without an href
     */
    public function __construct(
        public bool    $compact     = false,
        public bool    $interactive = false,
        public bool    $disabled    = false,
        public ?string $variant     = null,
        public ?string $tone        = null,
        public ?string $href        = null,
        public ?string $target      = null,
        public bool    $clickable   = false,
    ) {}

    public function render(): View|Closure|string
    {
        return view('webkernel::components.card');
    }

    /**
     * CSS class string for the root element.
     */
    public function rootClasses(): string
    {
        $classes = ['wcs-card'];

        if ($this->compact) {
            $classes[] = 'wcs-card--compact';
        }

        if ($this->interactive || $this->href || $this->clickable) {
            $classes[] = 'wcs-card--interactive';
        }

        if ($this->disabled) {
            $classes[] = 'wcs-card--disabled';
        }

        if ($this->variant) {
            $classes[] = 'wcs-card--' . $this->variant;
        }

        if ($this->tone) {
            $classes[] = 'wcs-card--tone-' . $this->tone;
        }

        return implode(' ', $classes);
    }

    /**
     * Inline accent color CSS variable derived from the tone prop.
     * Used by progress bars and highlighted values.
     */
    public function accentColor(): string
    {
        return match ($this->tone) {
            'emerald' => 'rgb(16,185,129)',
            'amber'   => 'rgb(245,158,11)',
            'red'     => 'rgb(239,68,68)',
            'blue'    => 'rgb(59,130,246)',
            'violet'  => 'rgb(139,92,246)',
            default   => 'rgb(var(--gray-400))',
        };
    }
}
