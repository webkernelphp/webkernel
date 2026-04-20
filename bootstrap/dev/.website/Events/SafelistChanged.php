<?php

declare(strict_types=1);

namespace Webkernel\Builders\Website\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when the Tailwind safelist has changed after a page save.
 *
 * Listen for this event to trigger a frontend rebuild (e.g. `npm run build`)
 * or notify developers that new CSS classes need to be compiled.
 */
class SafelistChanged
{
    use Dispatchable;

    /**
     * @param  array<string>  $added  New classes added to the safelist
     * @param  array<string>  $removed  Classes removed from the safelist
     * @param  string  $path  Path to the safelist file
     */
    public function __construct(
        public readonly array $added,
        public readonly array $removed,
        public readonly string $path,
    ) {}
}
