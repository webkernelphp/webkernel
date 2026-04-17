<?php

declare(strict_types=1);

namespace Webkernel\Traits;

use Webkernel\Generators\UniqueId\UniqueIdGenerator;

/**
 * HasIdentifiers trait.
 *
 * Adds identifier-generation capabilities to any class (Models, Components,
 * Livewire components, Filament resources, etc.).
 *
 * The single required method is `makeUniqueIdentifier()` which returns
 * a UniqueIdGenerator configured with sensible defaults.
 * All further customisation is done by chaining fluent methods.
 *
 * NOTE: Traits cannot be used directly inside Blade templates.
 *       Use the `IdentifierClass` bridge class instead, which wraps
 *       this trait and exposes it cleanly to views.
 *
 * @example — in a Model:
 *
 *   class Post extends Model
 *   {
 *       use HasIdentifiers;
 *
 *       public function getSlugId(): string
 *       {
 *           return $this->makeUniqueIdentifier()->using('cuid2')->length(24)->get();
 *       }
 *   }
 *
 * @example — in a Livewire / Filament component:
 *
 *   $elId = $this->makeUniqueIdentifier()->prefix('el_')->using('nano')->get();
 *
 * @example — in a Blade template (via IdentifierClass):
 *
 *   @php $gen = new \Webkernel\Generators\UniqueId\IdentifierClass @endphp
 *   <div id="{{ $gen->makeUniqueIdentifier()->get() }}">…</div>
 */
trait HasIdentifiers
{
    /**
     * Create a new fluent UniqueIdGenerator pre-configured with sane defaults.
     *
     * Default: epoch strategy, length 12, CSS-safe, no prefix.
     * Chain any fluent method to customise before calling a terminal method.
     *
     * Terminal methods:
     *   ->get()       → single string ID
     *   ->toArray()   → array of IDs
     *   ->toCss()     → ".id1, .id2" CSS selectors
     *   ->toRaw()     → "id1 id2" space-separated
     *   ->toJson()    → JSON array string
     *
     * @return UniqueIdGenerator
     */
    public function makeUniqueIdentifier(): UniqueIdGenerator
    {
        return UniqueIdGenerator::make();
    }
}
