<?php

declare(strict_types=1);

namespace Webkernel\Panel\Store;

use Illuminate\Support\Facades\Cache;
use JsonException;
use RuntimeException;

/**
 * Injectable store for panel runtime configuration.
 *
 * Backed by JSON files in storage/webkernel/panels/{panelId}.json.
 * Reads are served from Laravel's external cache (Redis/file) — Octane-safe
 * because no static PHP state is shared between requests.
 * Writes are atomic (tmp + rename) and bust the cache immediately.
 *
 * This is the single source of truth for panel brand overrides and any
 * other panel config that must be editable at runtime without touching
 * code or config/ files.
 *
 * Resolution in PanelProvider.assemble():
 *   1. build()             → developer defaults (logos, colors, structure)
 *   2. applyStoredBrand()  → store values WIN if present
 *
 * To edit from Filament UI:
 *   app(PanelConfigStore::class)->patch('system', ['brand_logo' => '/new-logo.png']);
 */
final class PanelConfigStore
{
    private const CACHE_TTL = 300;

    private const BRAND_KEYS = [
        'brand_name',
        'brand_logo',
        'brand_logo_dark',
        'brand_logo_height',
        'favicon',
        'primary_color',
    ];

    public function __construct(
        private readonly string $basePath,
    ) {}

    // -------------------------------------------------------------------------
    // Read
    // -------------------------------------------------------------------------

    /**
     * Return all stored data for a panel, or null when nothing is persisted.
     *
     * @return array<string, mixed>|null
     */
    public function find(string $panelId): ?array
    {
        $data = $this->read($panelId);

        return empty($data) ? null : $data;
    }

    /**
     * Return all stored panels as an id-keyed map.
     *
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        if (! is_dir($this->basePath)) {
            return [];
        }

        $panels = [];

        foreach (glob($this->basePath . DIRECTORY_SEPARATOR . '*.json') ?: [] as $file) {
            $id   = basename($file, '.json');
            $data = $this->read($id);

            if (! empty($data)) {
                $panels[$id] = $data;
            }
        }

        return $panels;
    }

    /**
     * Return only the brand-related fields for a panel.
     * Returns an empty array when nothing is stored.
     *
     * @return array<string, mixed>
     */
    public function brand(string $panelId): array
    {
        $data = $this->find($panelId) ?? [];

        return array_filter(
            array_intersect_key($data, array_flip(self::BRAND_KEYS)),
            static fn (mixed $v): bool => $v !== null && $v !== '',
        );
    }

    // -------------------------------------------------------------------------
    // Write
    // -------------------------------------------------------------------------

    /**
     * Persist the full data array for a panel (creates or replaces).
     *
     * @param array<string, mixed> $data
     */
    public function save(string $panelId, array $data): void
    {
        $this->write($panelId, $data);
    }

    /**
     * Merge $fields into the existing stored data for a panel.
     * Creates the entry if it does not exist yet.
     *
     * @param array<string, mixed> $fields
     */
    public function patch(string $panelId, array $fields): void
    {
        $existing = $this->find($panelId) ?? [];
        $this->write($panelId, array_merge($existing, $fields));
    }

    /**
     * Remove a panel's stored config from disk and cache.
     */
    public function remove(string $panelId): void
    {
        $path = $this->filePath($panelId);

        if (file_exists($path)) {
            unlink($path);
        }

        $this->flush($panelId);
    }

    /**
     * Force the next read to bypass cache and re-read from disk.
     */
    public function flush(string $panelId): void
    {
        Cache::forget($this->cacheKey($panelId));
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function read(string $panelId): array
    {
        return Cache::remember(
            $this->cacheKey($panelId),
            self::CACHE_TTL,
            function () use ($panelId): array {
                $path = $this->filePath($panelId);

                if (! file_exists($path)) {
                    return [];
                }

                $content = file_get_contents($path);

                if ($content === false || $content === '') {
                    return [];
                }

                try {
                    $data = json_decode($content, associative: true, flags: JSON_THROW_ON_ERROR);
                    return is_array($data) ? $data : [];
                } catch (JsonException) {
                    return [];
                }
            },
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function write(string $panelId, array $data): void
    {
        if (! is_dir($this->basePath) && ! mkdir($this->basePath, 0755, true) && ! is_dir($this->basePath)) {
            throw new RuntimeException("PanelConfigStore: cannot create directory [{$this->basePath}].");
        }

        $path = $this->filePath($panelId);
        $tmp  = $path . '.tmp.' . getmypid();

        file_put_contents(
            $tmp,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            LOCK_EX,
        );

        rename($tmp, $path);

        $this->flush($panelId);
    }

    private function cacheKey(string $panelId): string
    {
        return 'webkernel.panel.' . $panelId;
    }

    private function filePath(string $panelId): string
    {
        $safe = preg_replace('/[^a-z0-9_\-]/i', '_', $panelId);

        return $this->basePath . DIRECTORY_SEPARATOR . $safe . '.json';
    }
}
