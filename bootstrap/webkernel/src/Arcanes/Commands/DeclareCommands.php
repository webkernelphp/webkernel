<?php

declare(strict_types=1);

namespace Webkernel\Arcanes\Commands;

use Illuminate\Support\ServiceProvider;
use Webkernel\Console\CommandReplacer;

/**
 * Registers Webkernel command overrides on the Artisan kernel.
 *
 * Delegates entirely to {@see CommandReplacer::register()}, which uses
 * {@see \Illuminate\Console\Application::starting()} — a static boot hook
 * that fires once before the first Artisan dispatch, after all providers
 * have registered their commands.
 *
 * Nothing surfaces in `artisan` or `bootstrap/app.php`.
 */
final class DeclareCommands extends ServiceProvider
{
    /**
     * Wire up command overrides before the application boots.
     *
     * Placed in `register()` (not `boot()`) so the `starting` hook is
     * installed before any provider can boot and dispatch commands.
     *
     * @return void
     */
    public function register(): void
    {
        CommandReplacer::register($this->loadOverrides());
    }

    /**
     * Load the canonical override map from disk.
     *
     * Returns an empty array when the file is absent so the application
     * boots cleanly in stripped-down or test environments.
     *
     * @return array<string, class-string<\Illuminate\Console\Command>>
     */
    private function loadOverrides(): array
    {
        $path = defined('WEBKERNEL_PATH')
            ? WEBKERNEL_PATH . '/support/boot-actions/commands-overrides.php'
            : base_path('bootstrap/webkernel/support/boot-actions/commands-overrides.php');

        /** @var array<string, class-string<\Illuminate\Console\Command>> $map */
        $map = is_file($path) ? (require $path) : [];

        return $map;
    }
}
