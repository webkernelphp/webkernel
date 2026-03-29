<?php

declare(strict_types=1);

namespace Webkernel\Builders\Website\Console\Commands;

use Webkernel\Builders\Website\Models\Page;
use Illuminate\Console\Command;

class ExportCommand extends Command
{
    protected $signature = 'layup:export
                            {--output= : Output file path (default: stdout)}
                            {--status= : Filter by status (published, draft)}
                            {--pretty : Pretty-print JSON}';

    protected $description = 'Export all Layup pages as JSON';

    public function handle(): int
    {
        $modelClass = config('layup.pages.model', Page::class);
        $query = $modelClass::query();

        if ($status = $this->option('status')) {
            $query->where('status', $status);
        }

        $pages = $query->get()->map(fn ($page): array => [
            'title' => $page->title,
            'slug' => $page->slug,
            'status' => $page->status,
            'content' => $page->content,
            'meta' => $page->meta,
        ])->toArray();

        $flags = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        if ($this->option('pretty')) {
            $flags |= JSON_PRETTY_PRINT;
        }

        $json = json_encode(['pages' => $pages, 'exported_at' => now()->toIso8601String()], $flags);

        if ($output = $this->option('output')) {
            file_put_contents($output, $json);
            $this->info(__('layup::commands.exported', ['count' => count($pages), 'path' => $output]));
        } else {
            $this->line($json);
        }

        return self::SUCCESS;
    }
}
