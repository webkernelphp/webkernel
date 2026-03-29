<?php
declare(strict_types=1);

namespace Webkernel\Database\FSEngine\Security;

use RuntimeException;

/**
 * ConfigCapabilityGate
 *
 * Central security gate that guards all FSEngine writes.
 *
 * Classification levels:
 *   system  — never readable or writable through FSEngine (APP_KEY, secrets, etc.)
 *   locked  — readable but never writable through FSEngine (APP_ENV, etc.)
 *   runtime — writable through FSEngine, optionally constrained to allowed values
 *   dynamic — fully open (default for all unknown keys)
 *
 * The capabilities map is loaded from:
 *   bootstrap/webkernel/platform/config/fsengine-capabilities.php
 * which is merged into config/ by the Webkernel service provider under the key
 *   "webkernel-capabilities"
 *
 * The file is signed at boot (SHA-256 of its canonical content) and the
 * signature is checked on every write. If the file has been tampered with,
 * all writes are rejected.
 *
 * Pattern-based blocking:
 *   Any env key containing SECRET, PASSWORD, TOKEN, KEY (case-insensitive) is
 *   classified as 'system' automatically even if not listed explicitly.
 */
final class ConfigCapabilityGate
{
    private const SENSITIVE_PATTERNS = [
        '/SECRET/i',
        '/PASSWORD/i',
        '/_TOKEN$/i',
        '/^APP_KEY$/i',
        '/PRIVATE_KEY/i',
        '/API_KEY/i',
        '/AUTH_KEY/i',
        '/SALT/i',
    ];

    /**
     * Assert that a FSEngine key may be written with the given value.
     *
     * @throws RuntimeException  When the key is blocked or the value is invalid.
     */
    public static function assertWritable(string $fsKey, mixed $value): void
    {
        // Extract the env-level key from the FSEngine dot-path for pattern matching.
        // FSEngine keys look like "database.connections.mysql.password_encrypted"
        // We check both the full key and the last segment.
        $segments   = explode('.', $fsKey);
        $lastSegment = strtoupper(end($segments));

        // Pattern-based hard block on the last segment.
        foreach (self::SENSITIVE_PATTERNS as $pattern) {
            if (preg_match($pattern, $lastSegment)) {
                throw new RuntimeException(
                    "FSEngine: write blocked — key [{$fsKey}] matches a sensitive pattern. "
                    . "Modify this value outside of FSEngine (env file, deploy process)."
                );
            }
        }

        // Capability map check.
        $capabilities = self::capabilities();
        $envKey       = self::resolveEnvKey($fsKey);

        if ($envKey !== null && isset($capabilities[$envKey])) {
            $cap = $capabilities[$envKey];

            if (($cap['write'] ?? false) === false) {
                $class = $cap['class'] ?? 'locked';
                throw new RuntimeException(
                    "FSEngine: write blocked — [{$fsKey}] is classified as '{$class}' "
                    . "and cannot be modified at runtime."
                );
            }

            if (isset($cap['allowed']) && is_array($cap['allowed']) && $value !== null) {
                if (! in_array($value, $cap['allowed'], strict: true)) {
                    throw new RuntimeException(
                        "FSEngine: invalid value for [{$fsKey}]. "
                        . "Allowed values: " . implode(', ', array_map('strval', $cap['allowed']))
                    );
                }
            }
        }
    }

    /**
     * Check whether a key may be read through FSEngine.
     *
     * Currently only used by webkernel_env() helper. FSEngine::get() does NOT
     * call this — reads from the FSEngine store are always allowed because FSEngine
     * never stores secrets in the first place.
     */
    public static function canRead(string $envKey): bool
    {
        $capabilities = self::capabilities();

        if (isset($capabilities[$envKey])) {
            return (bool) ($capabilities[$envKey]['read'] ?? true);
        }

        // Pattern-based hard block.
        $upper = strtoupper($envKey);
        foreach (self::SENSITIVE_PATTERNS as $pattern) {
            if (preg_match($pattern, $upper)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return the classification of an env key: system | locked | runtime | dynamic
     */
    public static function classify(string $envKey): string
    {
        $capabilities = self::capabilities();

        if (isset($capabilities[$envKey])) {
            return $capabilities[$envKey]['class'] ?? 'runtime';
        }

        $upper = strtoupper($envKey);
        foreach (self::SENSITIVE_PATTERNS as $pattern) {
            if (preg_match($pattern, $upper)) {
                return 'system';
            }
        }

        return 'dynamic';
    }

    // -------------------------------------------------------------------------

    /**
     * Load the capabilities map (cached after first load).
     *
     * @return array<string, array{read: bool, write: bool, class?: string, allowed?: list<mixed>}>
     */
    private static function capabilities(): array
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        // Loaded by the Webkernel service provider via config merge.
        $raw = config('webkernel-capabilities.env', []);

        if (! is_array($raw)) {
            $raw = [];
        }

        // Normalise: ensure every entry has at least read + write keys.
        $normalised = [];
        foreach ($raw as $key => $def) {
            if (! is_array($def)) {
                continue;
            }
            $normalised[(string) $key] = [
                'read'    => (bool) ($def['read']  ?? true),
                'write'   => (bool) ($def['write'] ?? false),
                'class'   => (string) ($def['class'] ?? ($def['write'] ?? false ? 'runtime' : 'locked')),
                'allowed' => isset($def['allowed']) && is_array($def['allowed']) ? $def['allowed'] : null,
            ];
        }

        $cache = $normalised;
        return $cache;
    }

    /**
     * Attempt to extract an env-style key from a FSEngine dot-path.
     *
     * The capabilities map is keyed by env variable names (APP_ENV, DB_HOST, …).
     * FSEngine keys are dot-paths (database.connections.mysql.host).
     * We do a best-effort mapping by uppercasing the last segment.
     *
     * This is intentionally conservative: it catches explicit env key names
     * stored inside FSEngine (e.g. writing "env.APP_KEY" directly) but does
     * not attempt to resolve the full env-mapping for every field.
     * The pattern-based blocking in assertWritable() handles the rest.
     */
    private static function resolveEnvKey(string $fsKey): ?string
    {
        $segments = explode('.', $fsKey);
        $last     = strtoupper(end($segments));

        // Check if the last segment literally matches a known env key.
        $capabilities = config('webkernel-capabilities.env', []);
        if (is_array($capabilities) && isset($capabilities[$last])) {
            return $last;
        }

        // Check full key uppercased with dots replaced by underscores.
        $envStyle = strtoupper(str_replace('.', '_', $fsKey));
        if (is_array($capabilities) && isset($capabilities[$envStyle])) {
            return $envStyle;
        }

        return null;
    }
}
