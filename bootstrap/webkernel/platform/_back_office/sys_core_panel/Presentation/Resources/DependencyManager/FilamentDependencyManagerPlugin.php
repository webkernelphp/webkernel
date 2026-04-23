<?php

namespace Webkernel\BackOffice\System\Presentation\Resources\DependencyManager;

use Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Pages\DependencyManagerPage;
use Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Pages\NpmDependencyManagerPage;
use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentDependencyManagerPlugin implements Plugin
{
    public function getId(): string
    {
        return 'filament-dependency-manager';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            DependencyManagerPage::class,
            NpmDependencyManagerPage::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
