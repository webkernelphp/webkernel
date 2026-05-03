<?php
declare(strict_types=1);
/**
 * Command override map.
 *
 * Each entry replaces an original Artisan command name with a Webkernel
 * implementation. The original name is preserved as an alias so that any
 * third-party code calling the original still works transparently.
 *
 * @var array<string, class-string<\Illuminate\Console\Command>>
 */
return [
    // ── Filament panel commands ────────────────────────────────
    \Webkernel\Console\Commands\Panel::class => ['filament:panel', 'make:filament-panel', 'filament:make-panel'],
];
