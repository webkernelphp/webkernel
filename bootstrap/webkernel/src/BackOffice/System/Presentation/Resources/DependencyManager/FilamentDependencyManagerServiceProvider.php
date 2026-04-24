<?php

namespace Webkernel\BackOffice\System\Presentation\Resources\DependencyManager;

use Illuminate\Support\ServiceProvider;

class FilamentDependencyManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->loadViewsFrom(
            __DIR__ . '/resources/views',
            'webkernel-system'
        );

        $this->loadTranslationsFrom(
            __DIR__ . '/resources/lang',
            'dependency-manager'
        );
    }

    public function boot(): void
    {
        // Views are loaded in register
    }
}
