<?php declare(strict_types=1);

namespace Webkernel\System\Support;

/**
 * Process-lifetime in-memory cache for data that never changes within a worker.
 *
 * Cost: zero after the first read (hash-map lookup).
 * Suitable for: PHP version, SAPI, CPU core count, memory limit, OS info, etc.
 *
 * Never call remember() for per-request data like memory_get_usage().
 * Call reset() only from the Octane WorkerStarting hook.
 */
final class StaticDataCache
{
    private static array $store = [];

    public static function remember(string $key, \Closure $loader): mixed
    {
        return self::$store[$key] ??= $loader();
    }

    /**
     * Flush all cached values.
     * Only for Octane worker reset scenarios — never call per request.
     */
    public static function reset(): void
    {
        self::$store = [];
    }
}
