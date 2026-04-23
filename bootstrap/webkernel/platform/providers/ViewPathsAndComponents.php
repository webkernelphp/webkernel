<?php declare(strict_types=1);
namespace Webkernel\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Livewire\Blaze\Blaze;

class ViewPathsAndComponents
{
    public function __construct(protected Application $app) {}

    public function boot(): void
    {
        // Use prependNamespace instead of replace to ensure we don't break
        // Laravel's internal fallbacks if your custom folder is missing a specific file.
        View::prependNamespace('errors', [WEBKERNEL_ERRORS_PAGES_PATH]);

        // --- Load Views ---
        $quickTouchViewsPath     = WEBKERNEL_MAIN_SUPPORT_PATH . '/quick_touch';
        $webkernelViewsPath      = WEBKERNEL_MAIN_SUPPORT_PATH . '/views';
        $webkernelComponentsPath = $webkernelViewsPath . '/components';
        $svgExportPath           = WEBKERNEL_MAIN_SUPPORT_PATH . '/_dist/export-svg';
        $layupViewsPath          = resource_path('views');

        app('view')->addNamespace('webkernel', $webkernelViewsPath);
        app('view')->addNamespace('layup', $layupViewsPath);
        app('view')->addNamespace('webkernel-quick-touch', $quickTouchViewsPath);


        // --- Blade Components Namespace ---
        Blade::componentNamespace('Webkernel\\View\\Components', 'webkernel');

        // --- Global fallback: try "prefix::name.index" if view doesn't exist ---
        View::composer('*', function ($view) {
            $name = $view->getName();
            if (!view()->exists($name)) {
                $indexName = $name . '.index';
                if (view()->exists($indexName)) {
                    $view->name($indexName);
                }
            }
        });

        // --- Custom View Finder ---
        $this->app->bind('view.finder', function ($app) {
            return new IndexAwareViewFinder(
                $app['files'],
                $app['config']['view.paths']
            );
        });

        // --- Blaze Optimization ---
        Blaze::optimize()
            ->in($svgExportPath, compile: true, memo: false, fold: false)
            ->in($webkernelComponentsPath, compile: true, memo: false, fold: false);

       // Blaze::debug();
    }
}
