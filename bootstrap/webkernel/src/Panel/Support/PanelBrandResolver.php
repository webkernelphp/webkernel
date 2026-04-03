<?php

declare(strict_types=1);

namespace Webkernel\Panel\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Resolves panel brand assets (logo, name, colors, favicon) from multiple
 * sources in priority order:
 *
 *   1. Runtime overrides set via PanelBrandResolver::override()
 *   2. Database (table: webkernel_settings, scoped by prefix)
 *   3. Application config (config/webkernel.php or config/brand.php)
 *   4. Returns empty array — build() values in the panel act as final fallback
 *
 * Cache TTL defaults to 10 minutes. Bust it with PanelBrandResolver::flush().
 *
 * Schema expected (optional — resolver degrades gracefully if absent):
 *
 *   webkernel_settings
 *     - key   : string  e.g. "webkernel.brand.admin.logo"
 *     - value : string
 */
final class PanelBrandResolver
{
    private const TABLE = 'webkernel_settings';
    private const TTL   = 600;

    private const KEYS = ['logo', 'logo_dark', 'logo_height', 'name', 'favicon', 'colors'];

    /** Runtime overrides — keyed by prefix, then brand key. */
    private static array $overrides = [];

    /**
     * Resolve brand data for the given cache prefix.
     *
     * @return array<string, mixed>
     */
    public static function resolve(string $prefix): array
    {
        return Cache::remember(
            'panelbrand.' . $prefix,
            self::TTL,
            static fn () => self::build($prefix),
        );
    }

    /**
     * Set a runtime override (takes priority over DB and config).
     * Flushes the cache for the affected prefix.
     */
    public static function override(string $prefix, string $key, mixed $value): void
    {
        self::$overrides[$prefix][$key] = $value;
        self::flush($prefix);
    }

    /**
     * Flush cached brand data for a panel.
     */
    public static function flush(string $prefix): void
    {
        Cache::forget('panelbrand.' . $prefix);
    }

    // -------------------------------------------------------------------------

    private static function build(string $prefix): array
    {
        $brand = [];

        foreach (self::KEYS as $key) {
            $value = self::resolveKey($prefix, $key);

            if ($value !== null) {
                $brand[$key] = $key === 'colors' ? self::decodeColors($value) : $value;
            }
        }

        return $brand;
    }

    private static function resolveKey(string $prefix, string $key): mixed
    {
        // 1. Runtime overrides
        if (isset(self::$overrides[$prefix][$key])) {
            return self::$overrides[$prefix][$key];
        }

        // 2. Database
        $dbValue = self::fromDatabase($prefix, $key);
        if ($dbValue !== null) {
            return $dbValue;
        }

        // 3. Config
        return self::fromConfig($prefix, $key);
    }

    private static function fromDatabase(string $prefix, string $key): mixed
    {
        try {
            if (! Schema::hasTable(self::TABLE)) {
                return null;
            }

            $row = DB::table(self::TABLE)
                ->where('key', $prefix . '.' . $key)
                ->value('value');

            return $row !== null ? (string) $row : null;
        } catch (Throwable) {
            return null;
        }
    }

    private static function fromConfig(string $prefix, string $key): mixed
    {
        // Try "webkernel.brand.{suffix}.{key}" where suffix is last segment of prefix
        $suffix    = last(explode('.', $prefix));
        $configKey = 'webkernel.brand.' . $suffix . '.' . $key;
        $value     = config($configKey);

        if ($value !== null) {
            return $value;
        }

        // Fallback: "brand.{suffix}.{key}"
        return config('brand.' . $suffix . '.' . $key);
    }

    private static function decodeColors(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        try {
            $decoded = json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : [];
        } catch (Throwable) {
            return [];
        }
    }
}
