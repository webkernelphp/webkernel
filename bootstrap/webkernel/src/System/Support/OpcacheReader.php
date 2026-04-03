<?php declare(strict_types=1);

namespace Webkernel\System\Support;

/**
 * Static helpers for reading OPcache extension data.
 *
 * Centralises all opcache_get_status() calls so the rest of the
 * codebase never calls it directly. Safe under Octane — the result
 * is not statically cached here; caching is handled by the manager.
 */
final class OpcacheReader
{
    /**
     * Whether OPcache is loaded and enabled via php.ini.
     */
    public static function isEnabled(): bool
    {
        return extension_loaded('Zend OPcache')
            && (bool) ini_get('opcache.enable');
    }

    /**
     * Hit ratio as 0.0–100.0.
     * Returns null when OPcache is disabled or stats unavailable.
     */
    public static function hitRatio(): ?float
    {
        $s = self::status();

        if ($s === null) {
            return null;
        }

        $hits   = (int) ($s['opcache_statistics']['hits']   ?? 0);
        $misses = (int) ($s['opcache_statistics']['misses'] ?? 0);
        $total  = $hits + $misses;

        return $total > 0 ? round($hits / $total * 100.0, 1) : 0.0;
    }

    /**
     * Number of scripts cached in OPcache shared memory.
     * Returns null when OPcache is disabled.
     */
    public static function cachedScripts(): ?int
    {
        $s = self::status();

        return $s !== null
            ? (int) ($s['opcache_statistics']['num_cached_scripts'] ?? 0)
            : null;
    }

    /**
     * Shared memory bytes used by OPcache.
     * Returns null when OPcache is disabled.
     */
    public static function memoryUsed(): ?int
    {
        $s = self::status();

        return isset($s['memory_usage']['used_memory'])
            ? (int) $s['memory_usage']['used_memory']
            : null;
    }

    /**
     * Shared memory bytes free in OPcache.
     * Returns null when OPcache is disabled.
     */
    public static function memoryFree(): ?int
    {
        $s = self::status();

        return isset($s['memory_usage']['free_memory'])
            ? (int) $s['memory_usage']['free_memory']
            : null;
    }

    /**
     * Wasted memory percentage from OPcache fragmentation.
     * Returns null when OPcache is disabled.
     */
    public static function wastedPercentage(): ?float
    {
        $s = self::status();

        return $s !== null
            ? round((float) ($s['memory_usage']['current_wasted_percentage'] ?? 0.0), 1)
            : null;
    }

    /**
     * Raw opcache_get_status() result.
     * Returns null when OPcache is disabled or the function does not exist.
     *
     * @return array<string, mixed>|null
     */
    private static function status(): ?array
    {
        if (!self::isEnabled() || !function_exists('opcache_get_status')) {
            return null;
        }

        $result = @opcache_get_status(false);

        return is_array($result) ? $result : null;
    }
}
