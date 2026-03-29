<?php

declare(strict_types=1);

namespace Webkernel\Builders\Website\Console\Commands;

use Webkernel\Builders\Website\Support\SafelistCollector;
use Illuminate\Console\Command;

class GenerateSafelist extends Command
{
    protected $signature = 'layup:safelist
        {--output= : Output file path (default: from config)}
        {--stdout : Print to stdout instead of writing a file}
        {--static-only : Only include static plugin classes (no DB query)}';

    protected $description = 'Generate a Tailwind CSS safelist from Layup plugin classes and published page content';

    public function handle(): int
    {
        if ($this->option('static-only')) {
            $classes = SafelistCollector::staticClasses();
        } else {
            $classes = SafelistCollector::classes();
        }

        sort($classes);

        if ($this->option('stdout')) {
            $this->line(implode("\n", $classes));

            return self::SUCCESS;
        }

        $path = $this->option('output')
            ? base_path($this->option('output'))
            : SafelistCollector::defaultPath();

        $relativePath = ltrim(str_replace(base_path(), '', $path), '/');

        // Write the file
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, implode("\n", $classes) . "\n");

        $staticCount = count(SafelistCollector::staticClasses());
        $totalCount = count($classes);
        $dynamicCount = $totalCount - $staticCount;

        $this->info(__('layup::commands.safelist_wrote', [
            'total' => $totalCount,
            'path' => $relativePath,
            'static' => $staticCount,
            'dynamic' => max(0, $dynamicCount),
        ]));

        $this->newLine();
        $this->comment(__('layup::commands.safelist_tailwind_v4'));
        $this->newLine();
        $this->line("  @source \"../../{$relativePath}\";");
        $this->newLine();
        $this->comment(__('layup::commands.safelist_tailwind_v3'));
        $this->newLine();
        $this->line("  content: ['./{$relativePath}']");
        $this->newLine();
        $this->comment(__('layup::commands.safelist_tip'));
        $this->newLine();
        $this->line('  "build": "php artisan layup:safelist && vite build"');

        return self::SUCCESS;
    }
}
