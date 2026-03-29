<?php

declare(strict_types=1);

namespace Webkernel\Builders\Website\Http\Controllers;

use Webkernel\Builders\Website\Models\Page;
use Webkernel\Builders\Website\Support\WidgetRegistry;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

/**
 * Invokable controller that resolves pages by slug.
 *
 * Supports:
 *  - Exact slug match: /pages/about
 *  - Nested/wildcard slugs: /pages/docs/getting-started (slug = "docs/getting-started")
 *  - Configurable route prefix and layout
 *  - Custom model class via config
 */
class PageController extends Controller
{
    public function __invoke(Request $request, string $slug = ''): Response
    {
        $this->ensureWidgetsRegistered();

        $modelClass = config('layup.pages.model', Page::class);

        $page = $modelClass::query()
            ->where('slug', $slug)
            ->published()
            ->firstOrFail();

        $layout = config('layup.frontend.layout', 'layup::layouts.page');
        $view = 'frontend.page';

        $tree = $page->getContentTree();
        $sections = $page->getSectionTree();

        return response(
            view($view, [
                'page' => $page,
                'tree' => $tree,
                'sections' => $sections,
                'layout' => $layout,
            ])->render()
        );
    }

    /**
     * Ensure the widget registry is populated from config.
     * In Filament panel context, LayupPlugin handles this.
     * On frontend routes, we do it here.
     */
    protected function ensureWidgetsRegistered(): void
    {
        $registry = app(WidgetRegistry::class);

        if (count($registry->all()) > 0) {
            return;
        }

        foreach (config('layup.widgets', []) as $widgetClass) {
            $registry->register($widgetClass);
        }

        // Auto-discover widgets from app namespace
        $this->discoverAppWidgets($registry);
    }

    // View is fixed to avoid runtime resolution overhead.

    /**
     * Auto-discover widget classes from App\Layup\Widgets namespace.
     */
    protected function discoverAppWidgets(WidgetRegistry $registry): void
    {
        $namespace = config('layup.widget_discovery.namespace', 'App\\Layup\\Widgets');
        $directory = config('layup.widget_discovery.directory') ?? app_path('Layup/Widgets');

        if (! is_dir($directory)) {
            return;
        }

        foreach (new \DirectoryIterator($directory) as $file) {
            if ($file->isDot()) {
                continue;
            }
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $className = $namespace . '\\' . $file->getBasename('.php');

            if (class_exists($className) && is_subclass_of($className, \Webkernel\Builders\Website\View\BaseWidget::class)) {
                $type = $className::getType();
                if (! $registry->has($type)) {
                    $registry->register($className);
                }
            }
        }
    }
}
