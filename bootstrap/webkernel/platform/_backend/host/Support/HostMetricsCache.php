<?php declare(strict_types=1);

namespace Webkernel\System\Host\Support;

/**
 * File-based TTL cache for host metrics that change slowly (CPU load, RAM, disk).
 *
 * Tier B in the three-tier cache strategy:
 *   Tier A — process static properties (StaticDataCache)    — zero cost after 1st call
 *   Tier B — this class, file-backed with TTL              — ~0.2ms cache hit
 *   Tier C — live per-request (no caching)
 *
 * Writes are atomic on POSIX (tmp file + rename).
 * Survives cache:clear because it writes to storage/webkernel/metrics/, not
 * the Laravel cache store.
 *
 * When $ttl === 0, the loader is called directly with no caching.
 */
final class HostMetricsCache
{
    public static function remember(string $key, int $ttl, \Closure $loader): mixed
    {
        if ($ttl === 0) {
            return $loader();
        }

        $path = self::cachePath($key);

        if (is_file($path) && (time() - filemtime($path)) < $ttl) {
            $data = @unserialize((string) (@file_get_contents($path) ?: ''));
            if ($data !== false) {
                return $data;
            }
        }

        $fresh = $loader();
        self::write($path, $fresh);
        return $fresh;
    }

    /**
     * Prune stale cache files older than $maxAge seconds.
     * Intended for a scheduled command or the garbage-collection hook.
     */
    public static function prune(int $maxAge = 3600): void
    {
        $dir = storage_path('webkernel/metrics');
        if (!is_dir($dir)) {
            return;
        }
        foreach (glob($dir . '/*.cache') ?: [] as $file) {
            if ((time() - filemtime($file)) > $maxAge) {
                @unlink($file);
            }
        }
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private static function cachePath(string $key): string
    {
        $dir = storage_path('webkernel/metrics');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        // Key is slugified — no path traversal risk
        return $dir . '/' . preg_replace('/[^a-z0-9_]/', '_', $key) . '.cache';
    }

    private static function write(string $path, mixed $data): void
    {
        $tmp = $path . '.tmp.' . getmypid();
        @file_put_contents($tmp, serialize($data), LOCK_EX);
        @rename($tmp, $path);  // atomic on POSIX
    }
}
