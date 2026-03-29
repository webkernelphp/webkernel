<?php

declare(strict_types=1);

namespace Webkernel\Builders\Website\Console\Commands;

use Webkernel\Builders\Website\Models\Page;
use Webkernel\Builders\Website\Support\ContentValidator;
use Illuminate\Console\Command;

class ImportCommand extends Command
{
    protected $signature = 'layup:import
                            {file : JSON file to import}
                            {--dry-run : Validate without importing}
                            {--overwrite : Overwrite existing pages by slug}';

    protected $description = 'Import Layup pages from a JSON export file';

    public function handle(): int
    {
        $file = $this->argument('file');

        if (! file_exists($file)) {
            $this->error(__('layup::commands.file_not_found', ['path' => $file]));

            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($file), true);
        if (! is_array($data) || ! isset($data['pages'])) {
            $this->error(__('layup::commands.invalid_export'));

            return self::FAILURE;
        }

        $modelClass = config('layup.pages.model', Page::class);
        $validator = new ContentValidator;
        $imported = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($data['pages'] as $pageData) {
            $slug = $pageData['slug'] ?? null;
            if (! $slug) {
                $this->warn(__('layup::commands.skipping_no_slug'));
                $skipped++;

                continue;
            }

            // Validate content
            $result = $validator->validate($pageData['content'] ?? ['rows' => []]);
            if (! $result->passes()) {
                $this->warn(__('layup::commands.invalid_content', ['slug' => $slug, 'errors' => implode(', ', $result->errors())]));
                $errors++;

                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("✓ {$slug} — valid");
                $imported++;

                continue;
            }

            $existing = $modelClass::withTrashed()->where('slug', $slug)->first();

            if ($existing && ! $this->option('overwrite')) {
                $this->warn(__('layup::commands.skipping_exists', ['slug' => $slug]));
                $skipped++;

                continue;
            }

            if ($existing) {
                $existing->update([
                    'title' => $pageData['title'] ?? $existing->title,
                    'content' => $pageData['content'] ?? $existing->content,
                    'meta' => $pageData['meta'] ?? $existing->meta,
                    'status' => $pageData['status'] ?? $existing->status,
                ]);
                $this->info(__('layup::commands.updated_page', ['slug' => $slug]));
            } else {
                $modelClass::create([
                    'title' => $pageData['title'] ?? ucfirst((string) $slug),
                    'slug' => $slug,
                    'content' => $pageData['content'] ?? ['rows' => []],
                    'meta' => $pageData['meta'] ?? [],
                    'status' => $pageData['status'] ?? 'draft',
                ]);
                $this->info(__('layup::commands.created_page', ['slug' => $slug]));
            }

            $imported++;
        }

        $this->newLine();
        $action = $this->option('dry-run') ? __('layup::commands.validated') : __('layup::commands.imported');
        $this->info(__('layup::commands.import_summary', ['action' => $action, 'imported' => $imported, 'skipped' => $skipped, 'errors' => $errors]));

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
