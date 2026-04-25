<?php declare(strict_types=1);

namespace Webkernel\Providers;

use Illuminate\Support\ServiceProvider;
use Webkernel\Console\RunJobCommand;

class CommandServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([
            RunJobCommand::class,
        ]);
    }
}
