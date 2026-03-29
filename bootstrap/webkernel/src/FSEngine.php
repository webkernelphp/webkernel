<?php
declare(strict_types=1);

namespace Webkernel;

use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Webkernel\Database\FSEngine\Security\ConfigCapabilityGate;

/**
 * FSEngine — runtime-editable config layer alongside Laravel's config().
 *
 * Resolution order for a given key:
 *   1. FSEngine store  (runtime overrides)
 *   2. Laravel config() (developer defaults)
 *   3. $default argument
 *
 * All writes pass through ConfigCapabilityGate — keys classified as 'system'
 * are unconditionally rejected. Keys classified as 'runtime' are accepted only
 * when their new value satisfies the allowed-values constraint (if any).
 *
 * The store is a flat key → PHP array map persisted as individual PHP files
 * per namespace under storage/webkernel/config/ (overridable via
 * config/webkernel/fsengine.php → path).
 *
 * Keys use dot-notation: "panels.admin.brand_name"
 *   namespace = "panels"
 *   remainder = "admin.brand_name"
 */
final class FSEngine
{
    /** In-memory cache of loaded namespaces. */
    private static array $store = [];

    /** Namespaces whose disk files have been loaded. */
    private static array $loaded = [];

    // -------------------------------------------------------------------------
    // Public API — reads
    // -------------------------------------------------------------------------

    /**
     * Read a value.
     *
     * No gate check on reads — reads are always allowed.
     * Sensitive values that must never be exposed should simply not be stored
     * in FSEngine in the first place (passwords, APP_KEY, etc.).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        [$ns, $remainder] = self::splitKey($key);
        self::loadNamespace($ns);

        $value = self::dotGet(self::$store[$ns] ?? [], $remainder);
        if ($value !== null) {
            return $value;
        }

        $laravelValue = config($key);
        return $laravelValue !== null ? $laravelValue : $default;
    }

    /**
     * Return the raw nested array for a namespace.
     *
     * @return array<string, mixed>
     */
    public static function namespace(string $namespace): array
    {
        self::loadNamespace($namespace);
        return self::$store[$namespace] ?? [];
    }

    /**
     * Return all keys in a namespace as a flat dot-notation array.
     *
     * @return array<string, mixed>
     */
    public static function all(string $namespace): array
    {
        self::loadNamespace($namespace);
        return self::flatten(self::$store[$namespace] ?? [], $namespace);
    }

    // -------------------------------------------------------------------------
    // Public API — writes (all gated)
    // -------------------------------------------------------------------------

    /**
     * Write a value and persist the namespace file immediately.
     *
     * @throws RuntimeException  When the key is classified as 'system' or the
     *                           value is not in the allowed set for 'runtime' keys.
     */
    public static function set(string $key, mixed $value): void
    {
        ConfigCapabilityGate::assertWritable($key, $value);

        [$ns, $remainder] = self::splitKey($key);
        self::loadNamespace($ns);
        self::dotSet(self::$store[$ns], $remainder, $value);
        self::persist($ns);
        self::bustCache($ns);
    }

    /**
     * Patch multiple keys in one write cycle (single persist per namespace).
     *
     * Each key is individually gate-checked. If any key fails, the entire batch
     * is aborted before any disk write occurs.
     *
     * @param array<string, mixed> $values  Dot-notation keys → values.
     *
     * @throws RuntimeException  When any key fails the capability gate.
     */
    public static function patch(array $values): void
    {
        // Gate-check all keys before touching anything.
        foreach ($values as $key => $value) {
            ConfigCapabilityGate::assertWritable($key, $value);
        }

        $byNs = [];
        foreach ($values as $key => $value) {
            [$ns, $remainder] = self::splitKey($key);
            self::loadNamespace($ns);
            self::dotSet(self::$store[$ns], $remainder, $value);
            $byNs[$ns] = true;
        }

        foreach (array_keys($byNs) as $ns) {
            self::persist($ns);
            self::bustCache($ns);
        }
    }

    /**
     * Remove a key. Persists the namespace file.
     */
    public static function forget(string $key): void
    {
        ConfigCapabilityGate::assertWritable($key, null);

        [$ns, $remainder] = self::splitKey($key);
        self::loadNamespace($ns);
        self::dotForget(self::$store[$ns], $remainder);
        self::persist($ns);
        self::bustCache($ns);
    }

    /**
     * Completely replace a namespace's data and persist.
     *
     * Each key inside $data is gate-checked. Batch-abort semantics apply.
     *
     * @param array<string, mixed> $data  Nested or dot-notation array.
     */
    public static function put(string $namespace, array $data): void
    {
        foreach (self::flatten($data, $namespace) as $key => $value) {
            ConfigCapabilityGate::assertWritable($key, $value);
        }

        self::$store[$namespace] = $data;
        self::$loaded[$namespace] = true;
        self::persist($namespace);
        self::bustCache($namespace);
    }

    /**
     * Drop an entire namespace from disk and memory.
     */
    public static function dropNamespace(string $namespace): void
    {
        $path = self::namespacePath($namespace);
        if (file_exists($path)) {
            unlink($path);
        }
        unset(self::$store[$namespace], self::$loaded[$namespace]);
        self::bustCache($namespace);
    }

    /**
     * Force a reload of a namespace from disk on the next access.
     */
    public static function invalidate(string $namespace): void
    {
        unset(self::$store[$namespace], self::$loaded[$namespace]);
        self::bustCache($namespace);
    }

    /**
     * Invalidate all loaded namespaces.
     */
    public static function invalidateAll(): void
    {
        foreach (array_keys(self::$loaded) as $ns) {
            self::invalidate($ns);
        }
    }

    // -------------------------------------------------------------------------
    // Fingerprint / change detection
    // -------------------------------------------------------------------------

    /**
     * Return an MD5 fingerprint of the namespace's current on-disk content.
     * Returns null when the file does not exist yet.
     */
    public static function fingerprint(string $namespace): ?string
    {
        $path = self::namespacePath($namespace);
        return file_exists($path) ? md5_file($path) ?: null : null;
    }

    // -------------------------------------------------------------------------
    // Filesystem helpers
    // -------------------------------------------------------------------------

    public static function basePath(): string
    {
        return config('webkernel.fsengine.path', storage_path('webkernel/config'));
    }

    public static function namespacePath(string $namespace): string
    {
        $safe = preg_replace('/[^a-z0-9_\-]/i', '_', $namespace);
        return self::basePath() . DIRECTORY_SEPARATOR . $safe . '.php';
    }

    // -------------------------------------------------------------------------
    // Internal load / persist
    // -------------------------------------------------------------------------

    private static function loadNamespace(string $ns): void
    {
        if (isset(self::$loaded[$ns])) {
            return;
        }

        $path = self::namespacePath($ns);
        if (file_exists($path)) {
            $data = require $path;
            self::$store[$ns] = is_array($data) ? $data : [];
        } else {
            self::$store[$ns] = [];
        }

        self::$loaded[$ns] = true;
    }

    private static function persist(string $ns): void
    {
        $dir = self::basePath();
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new RuntimeException("FSEngine: cannot create directory [{$dir}].");
        }

        $path   = self::namespacePath($ns);
        $data   = self::$store[$ns] ?? [];
        $export = var_export($data, true);

        $content = "<?php\n\n// FSEngine — auto-generated, do not edit manually.\n// Namespace: {$ns}\n\nreturn {$export};\n";

        $tmp = $path . '.tmp.' . getmypid();
        file_put_contents($tmp, $content, LOCK_EX);
        rename($tmp, $path);

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }
    }

    private static function bustCache(string $ns): void
    {
        Cache::forget("fsengine.{$ns}");
    }

    // -------------------------------------------------------------------------
    // Dot-notation helpers
    // -------------------------------------------------------------------------

    private static function splitKey(string $key): array
    {
        $dot = strpos($key, '.');
        if ($dot === false) {
            return [$key, ''];
        }
        return [substr($key, 0, $dot), substr($key, $dot + 1)];
    }

    private static function dotGet(array $data, string $key): mixed
    {
        if ($key === '') {
            return $data ?: null;
        }
        foreach (explode('.', $key) as $segment) {
            if (! is_array($data) || ! array_key_exists($segment, $data)) {
                return null;
            }
            $data = $data[$segment];
        }
        return $data;
    }

    private static function dotSet(array &$data, string $key, mixed $value): void
    {
        if ($key === '') {
            $data = is_array($value) ? $value : [];
            return;
        }
        $segments = explode('.', $key);
        $current  = &$data;
        foreach ($segments as $segment) {
            if (! isset($current[$segment]) || ! is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }
        $current = $value;
    }

    private static function dotForget(array &$data, string $key): void
    {
        if ($key === '') {
            $data = [];
            return;
        }
        $segments = explode('.', $key);
        $last     = array_pop($segments);
        $current  = &$data;
        foreach ($segments as $segment) {
            if (! is_array($current[$segment] ?? null)) {
                return;
            }
            $current = &$current[$segment];
        }
        unset($current[$last]);
    }

    private static function flatten(array $data, string $prefix = ''): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $fullKey = $prefix !== '' ? "{$prefix}.{$key}" : (string) $key;
            if (is_array($value)) {
                $result += self::flatten($value, $fullKey);
            } else {
                $result[$fullKey] = $value;
            }
        }
        return $result;
    }
}
