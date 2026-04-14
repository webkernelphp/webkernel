<?php declare(strict_types=1);

namespace Webkernel\System\Services;

use Illuminate\Support\Facades\Http;

/**
 * Fetches the list of active PHP releases from php.net and caches
 * the result locally so the system can recommend the latest stable patch.
 *
 * Cache path : config('webkernel-system.php_releases_cache')
 * TTL        : config('webkernel-system.php_releases_ttl')  (default 86400 s)
 * API URL    : config('webkernel-system.php_releases_api')
 *
 * The cache is written to disk (not through Laravel's cache layer)
 * so it survives cache:clear and is available before the app fully boots.
 */
final class PhpReleasesService
{
    /**
     * Return the cached releases array, refreshing from php.net when stale.
     *
     * @return array<string, mixed>  Keyed by version string.
     */
    public function releases(): array
    {
        if ($this->isCacheValid()) {
            return $this->readCache();
        }

        return $this->fetchAndCache();
    }

    /**
     * Latest patch version for the given minor series.
     *
     * Example: latestPatchFor('8.4') => '8.4.6'
     * Returns null when the series is absent from the releases data.
     */
    public function latestPatchFor(string $series): ?string
    {
        $releases = $this->releases();

        $matching = array_filter(
            array_keys($releases),
            static fn(string $v): bool => str_starts_with($v, $series . '.'),
        );

        if (empty($matching)) {
            return null;
        }

        usort($matching, 'version_compare');

        return end($matching) ?: null;
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function cachePath(): string
    {
        return (string) config(
            'webkernel-system.php_releases_cache',
            storage_path('webkernel/php-releases.json'),
        );
    }

    private function ttl(): int
    {
        return (int) config('webkernel-system.php_releases_ttl', 86400);
    }

    private function apiUrl(): string
    {
        return (string) config(
            'webkernel-system.php_releases_api',
            'https://www.php.net/releases/index.php?json&version=8&max=10',
        );
    }

    private function isCacheValid(): bool
    {
        $file = $this->cachePath();

        if (! is_file($file)) {
            return false;
        }

        return (time() - (int) filemtime($file)) < $this->ttl();
    }

    /**
     * @return array<string, mixed>
     */
    private function readCache(): array
    {
        $raw = @file_get_contents($this->cachePath());

        if ($raw === false) {
            return [];
        }

        return json_decode($raw, true) ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchAndCache(): array
    {
        try {
            $response = Http::timeout(10)->get($this->apiUrl());

            if (! $response->successful()) {
                return $this->readCache();
            }

            $data = $response->json() ?? [];

            $path = $this->cachePath();
            $dir  = dirname($path);

            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($path, json_encode($data));

            return $data;
        } catch (\Throwable) {
            return $this->readCache();
        }
    }
}
