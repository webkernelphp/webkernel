<?php declare(strict_types=1);

namespace Webkernel\System\Host\Support;

/**
 * Static helpers for reading Linux /proc filesystem entries.
 *
 * All reads are silenced with @ and return sensible zero-value defaults
 * when unreadable, when not on Linux, or when open_basedir prevents access.
 * Safe under Octane and shared hosting environments.
 */
final class ProcReader
{
    /**
     * Read /proc/meminfo and return a named map of kilobyte values.
     *
     * @return array<string, int>  Key => kB value. Empty on non-Linux or inaccessible /proc.
     */
    public static function meminfo(): array
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            return [];
        }

        if (!self::canAccessProc()) {
            return [];
        }

        $raw = (string) (@file_get_contents('/proc/meminfo') ?: '');
        if ($raw === '') {
            return [];
        }

        $result = [];
        preg_match_all('/^(\w+):\s+(\d+)/m', $raw, $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            $result[$m[1]] = (int) $m[2];
        }

        return $result;
    }

    /**
     * Read /proc/loadavg and return the three load averages.
     *
     * @return array{float, float, float}  [1min, 5min, 15min]
     */
    public static function loadavg(): array
    {
        if (PHP_OS_FAMILY === 'Linux' && self::canAccessProc()) {
            $raw   = (string) (@file_get_contents('/proc/loadavg') ?: '');
            $parts = explode(' ', trim($raw));

            return [
                (float) ($parts[0] ?? 0.0),
                (float) ($parts[1] ?? 0.0),
                (float) ($parts[2] ?? 0.0),
            ];
        }

        $la = sys_getloadavg() ?: [0.0, 0.0, 0.0];
        return [(float) $la[0], (float) $la[1], (float) $la[2]];
    }

    /**
     * Count CPU cores from /proc/cpuinfo.
     * Returns 1 as a safe minimum when unreadable or inaccessible.
     */
    public static function cpuCores(): int
    {
        if (PHP_OS_FAMILY === 'Linux' && self::canAccessProc()) {
            $raw = (string) (@file_get_contents('/proc/cpuinfo') ?: '');
            preg_match_all('/^processor/m', $raw, $matches);
            return max(1, \count($matches[0]));
        }

        return 1;
    }

    /**
     * Read host uptime in seconds from /proc/uptime.
     * Returns 0 when unreadable or inaccessible.
     */
    public static function uptimeSeconds(): int
    {
        if (PHP_OS_FAMILY === 'Linux' && self::canAccessProc()) {
            $raw = (string) (@file_get_contents('/proc/uptime') ?: '');
            if ($raw !== '') {
                return (int) floatval(explode(' ', $raw)[0] ?? 0);
            }
        }

        return 0;
    }

    /**
     * Count total processes by scanning /proc for numeric entries.
     * Returns 0 when unreadable or inaccessible.
     */
    public static function processCount(): int
    {
        if (!self::canAccessProc()) {
            return 0;
        }

        $count = 0;
        foreach ((@scandir('/proc') ?: []) as $entry) {
            if (ctype_digit($entry)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Count PHP-FPM workers by reading /proc/[pid]/cmdline.
     *
     * @return array{int, int}  [activeWorkers, totalWorkers]
     * Returns [0,0] when inaccessible or unreadable.
     */
    public static function fpmWorkers(): array
    {
        if (!self::canAccessProc()) {
            return [0, 0];
        }

        $total  = 0;
        $active = 0;

        foreach ((@scandir('/proc') ?: []) as $entry) {
            if (!ctype_digit($entry)) {
                continue;
            }

            $cmdline = @file_get_contents("/proc/{$entry}/cmdline");
            if ($cmdline === false || !str_contains($cmdline, 'php-fpm')) {
                continue;
            }

            $total++;

            $status = @file_get_contents("/proc/{$entry}/status") ?: '';
            if (preg_match('/^State:\s+R\s/m', $status)) {
                $active++;
            }
        }

        return [$active, $total];
    }

    /**
     * Read kernel entropy pool size in bits.
     * Returns 0 when unreadable or inaccessible.
     */
    public static function entropyAvailable(): int
    {
        if (!self::canAccessProc()) {
            return 0;
        }

        return (int) (@file_get_contents('/proc/sys/kernel/random/entropy_avail') ?: 0);
    }

    /**
     * Read a single named key from /proc/meminfo.
     * Returns the value in kilobytes. Returns 0 when key is absent or inaccessible.
     */
    public static function meminfoKey(string $key): int
    {
        $map = self::meminfo();
        return $map[$key] ?? 0;
    }

    /**
     * Check if /proc is accessible considering open_basedir restrictions.
     *
     * Uses set_error_handler to prevent custom error handlers (e.g. Laravel's)
     * from converting E_WARNING into exceptions — @ alone is not sufficient.
     *
     * @return bool
     */
    private static function canAccessProc(): bool
    {
        // Parse open_basedir paths properly before touching the filesystem
        $openBasedir = ini_get('open_basedir');
        if ($openBasedir !== false && $openBasedir !== '') {
            $procAllowed = false;
            foreach (explode(PATH_SEPARATOR, $openBasedir) as $dir) {
                $dir = rtrim($dir, '/\\');
                // /proc is allowed if a path equals '/proc' or is a parent of it
                if ($dir === '/proc' || str_starts_with('/proc', $dir . '/')) {
                    $procAllowed = true;
                    break;
                }
            }
            if (!$procAllowed) {
                return false;
            }
        }

        // Install a silent handler so open_basedir warnings never bubble up,
        // even when a global handler converts errors to exceptions.
        $prev = set_error_handler(static fn() => true);
        try {
            return is_dir('/proc');
        } catch (\Throwable) {
            return false;
        } finally {
            set_error_handler($prev);
        }
    }
}
