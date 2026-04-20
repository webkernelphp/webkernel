<?php

declare(strict_types=1);

namespace Webkernel\Builders\Website\Console\Commands;

use Webkernel\Builders\Website\Models\Page;
use Webkernel\Builders\Website\Support\ContentValidator;
use Webkernel\Builders\Website\Support\SafelistCollector;
use Webkernel\Builders\Website\Support\WidgetRegistry;
use Illuminate\Console\Command;

class AuditCommand extends Command
{
    protected $signature = 'layup:audit';

    protected $description = 'Audit Layup pages — check for broken widgets, unused classes, and content issues';

    public function handle(): int
    {
        $modelClass = config('layup.pages.model', Page::class);
        $pages = $modelClass::all();

        $this->info(__('layup::commands.audit_report'));
        $this->line(str_repeat('─', 40));
        $this->newLine();

        // Page stats
        $published = $pages->where('status', 'published')->count();
        $drafts = $pages->where('status', 'draft')->count();
        $this->line(__('layup::commands.pages_count', ['total' => $pages->count(), 'published' => $published, 'drafts' => $drafts]));

        // Widget registry
        $registry = app(WidgetRegistry::class);
        foreach (config('layup.widgets', []) as $class) {
            if (class_exists($class) && ! $registry->has($class::getType())) {
                $registry->register($class);
            }
        }
        $this->line(__('layup::commands.registered_widgets', ['count' => count($registry->all())]));

        // Validate all pages
        $validator = new ContentValidator(strict: true);
        $issues = [];
        $widgetUsage = [];
        $totalWidgets = 0;

        foreach ($pages as $page) {
            $result = $validator->validate($page->content ?? ['rows' => []]);
            if (! $result->passes()) {
                $issues[$page->title] = $result->errors();
            }

            // Count widget usage
            foreach ($page->content['rows'] ?? [] as $row) {
                foreach ($row['columns'] ?? [] as $col) {
                    foreach ($col['widgets'] ?? [] as $widget) {
                        $type = $widget['type'] ?? 'unknown';
                        $widgetUsage[$type] = ($widgetUsage[$type] ?? 0) + 1;
                        $totalWidgets++;
                    }
                }
            }
        }

        $this->line(__('layup::commands.total_widget_instances', ['count' => $totalWidgets]));

        // Widget usage breakdown
        if ($widgetUsage !== []) {
            arsort($widgetUsage);
            $this->newLine();
            $this->line(__('layup::commands.widget_usage'));
            foreach ($widgetUsage as $type => $count) {
                $registered = $registry->has($type) ? '✓' : '✗';
                $this->line("  {$registered} {$type}: {$count}");
            }
        }

        // Validation issues
        if ($issues !== []) {
            $this->newLine();
            $this->warn(__('layup::commands.content_issues'));
            foreach ($issues as $title => $errors) {
                $this->line("  {$title}:");
                foreach ($errors as $error) {
                    $this->line("    - {$error}");
                }
            }
        } else {
            $this->newLine();
            $this->info(__('layup::commands.all_pages_valid'));
        }

        // Safelist status
        $staticCount = count(SafelistCollector::staticClasses());
        $allCount = count(SafelistCollector::classes());
        $dynamicCount = $allCount - $staticCount;
        $this->newLine();
        $this->line(__('layup::commands.safelist_count', ['total' => $allCount, 'static' => $staticCount, 'dynamic' => $dynamicCount]));

        // Revisions
        if (config('layup.revisions.enabled')) {
            $revisionCount = \Webkernel\Builders\Website\Models\PageRevision::count();
            $this->line(__('layup::commands.revisions_count', ['count' => $revisionCount]));
        }

        return self::SUCCESS;
    }
}
