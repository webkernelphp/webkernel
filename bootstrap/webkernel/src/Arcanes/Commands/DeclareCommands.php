<?php

declare(strict_types=1);

namespace Webkernel\Arcanes\Commands;

use Illuminate\Support\ServiceProvider;
use Webkernel\Commands\CommandReplacer;
use Webkernel\Commands\Module\InstallCommand as ModuleInstallCommand;
use Webkernel\Commands\Kernel\UpdateCommand  as KernelUpdateCommand;

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
     * Bootstrap application Commands.
     *
     * @return void
     */
    public function boot(): void
    {
        // Check if the application is running in the console to avoid unnecessary overhead
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeModule::class,
                ModuleInstallCommand::class,
                KernelUpdateCommand::class,
            ]);
        }
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
        $path = WEBKERNEL_PLATFORM_CMD_OVERRIDES;

        /** @var array<string, class-string<\Illuminate\Console\Command>> $map */
        $map = is_file($path) ? (require $path) : [];

        return $map;
    }
}
