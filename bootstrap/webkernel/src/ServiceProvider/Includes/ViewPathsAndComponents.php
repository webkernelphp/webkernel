<?php declare(strict_types=1);
namespace Webkernel\ServiceProvider\Includes;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Livewire\Blaze\Blaze;

class ViewPathsAndComponents
{
    public function __construct(protected Application $app) {}

    public function boot(): void
    {
        $webkernelViewsPath      = WEBKERNEL_PATH . '/runtime/dist/view/views';
        $webkernelComponentsPath = $webkernelViewsPath . '/components';
        $svgExportPath           = WEBKERNEL_PATH . '/runtime/dist/export-svg';
        $layupViewsPath          = resource_path('views');

        // --- Load Views ---
        app('view')->addNamespace('webkernel', $webkernelViewsPath);
        app('view')->addNamespace('layup', $layupViewsPath);

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
    }
}
