<?php

declare(strict_types=1);

namespace Webkernel\Builders\Website\Support;

class PageTemplate
{
    /**
     * Get all available templates (built-in + published).
     *
     * @return array<string, array{name: string, description: string, content: array}>
     */
    public static function all(): array
    {
        $templates = [];

        // Built-in templates
        $builtInPath = dirname(__DIR__, 2) . '/resources/templates';
        $templates = array_merge($templates, static::loadFromDirectory($builtInPath));

        // Published/custom templates
        $customPath = resource_path('layup/templates');
        if (is_dir($customPath)) {
            return array_merge($templates, static::loadFromDirectory($customPath));
        }

        return $templates;
    }

    /**
     * Load templates from a directory.
     *
     * @return array<string, array>
     */
    protected static function loadFromDirectory(string $path): array
    {
        if (! is_dir($path)) {
            return [];
        }

        $templates = [];
        foreach (new \DirectoryIterator($path) as $file) {
            if ($file->isDot()) {
                continue;
            }
            if ($file->getExtension() !== 'json') {
                continue;
            }
            $slug = $file->getBasename('.json');
            $data = json_decode(file_get_contents($file->getPathname()), true);

            if ($data && isset($data['content'])) {
                $templates[$slug] = [
                    'name' => $data['name'] ?? ucfirst($slug),
                    'description' => $data['description'] ?? '',
                    'thumbnail' => $data['thumbnail'] ?? null,
                    'content' => $data['content'],
                ];
            }
        }

        return $templates;
    }

    /**
     * Get a single template by slug.
     */
    public static function get(string $slug): ?array
    {
        return static::all()[$slug] ?? null;
    }

    /**
     * Get template options for a select dropdown.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(static::all())
            ->mapWithKeys(fn (array $t, string $slug): array => [$slug => $t['name']])
            ->all();
    }

    /**
     * Save a page's content as a custom template.
     */
    public static function saveFromPage(string $name, array $content, ?string $description = null): string
    {
        $slug = \Illuminate\Support\Str::slug($name);
        $path = resource_path('layup/templates');

        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $data = [
            'name' => $name,
            'description' => $description ?? '',
            'thumbnail' => null,
            'content' => $content,
        ];

        file_put_contents(
            "{$path}/{$slug}.json",
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );

        return $slug;
    }
}
