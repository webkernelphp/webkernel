<?php declare(strict_types=1);

namespace Webkernel\System\Support;

/**
 * Static helpers for reading Linux /proc filesystem entries.
 *
 * All reads are silenced with @ and return sensible zero-value defaults
 * when unreadable or when not on Linux. Safe under Octane.
 */
final class ProcReader
{
    /**
     * Read /proc/meminfo and return a named map of kilobyte values.
     *
     * @return array<string, int>  Key => kB value. Empty on non-Linux.
     */
    public static function meminfo(): array
    {
        if (PHP_OS_FAMILY !== 'Linux') {
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
     * @return array{float, float, float}
     */
    public static function loadavg(): array
    {
        if (is_file('/proc/loadavg')) {
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
     * Returns 1 as a safe minimum when unreadable.
     */
    public static function cpuCores(): int
    {
        if (is_file('/proc/cpuinfo')) {
            $raw = (string) (@file_get_contents('/proc/cpuinfo') ?: '');
            preg_match_all('/^processor/m', $raw, $m);

            return max(1, count($m[0]));
        }

        return 1;
    }

    /**
     * Read host uptime in seconds from /proc/uptime.
     * Returns 0 when unreadable.
     */
    public static function uptimeSeconds(): int
    {
        if (!is_file('/proc/uptime')) {
            return 0;
        }

        $raw = (string) (@file_get_contents('/proc/uptime') ?: '');

        if ($raw === '') {
            return 0;
        }

        return (int) floatval(explode(' ', $raw)[0] ?? 0);
    }

    /**
     * Count total processes by scanning /proc for numeric entries.
     * Returns 0 on non-Linux or when /proc is unreadable.
     */
    public static function processCount(): int
    {
        if (!is_dir('/proc')) {
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
     * Count PHP-FPM workers from /proc/[pid]/cmdline.
     *
     * No shell_exec. Pure procfs reads.
     *
     * @return array{?int, ?int}  [activeWorkers, totalWorkers]
     */
    public static function fpmWorkers(): array
    {
        if (!is_dir('/proc')) {
            return [null, null];
        }

        $total  = 0;
        $active = 0;

        try {
            foreach ((@scandir('/proc') ?: []) as $entry) {
                if (!ctype_digit($entry)) {
                    continue;
                }

                $cmdline = @file_get_contents("/proc/{$entry}/cmdline");

                if ($cmdline === false || !str_contains((string) $cmdline, 'php-fpm')) {
                    continue;
                }

                $total++;

                $status = (string) (@file_get_contents("/proc/{$entry}/status") ?: '');

                if (preg_match('/^State:\s+R\s/m', $status)) {
                    $active++;
                }
            }
        } catch (\Throwable) {
            return [null, null];
        }

        return $total > 0 ? [$active, $total] : [null, null];
    }

    /**
     * Read kernel entropy pool size in bits.
     * Returns 0 when unreadable.
     */
    public static function entropyAvailable(): int
    {
        return (int) (@file_get_contents('/proc/sys/kernel/random/entropy_avail') ?: 0);
    }

    /**
     * Read a single named key from /proc/meminfo.
     * Returns the value in kilobytes. Returns 0 when key is absent.
     */
    public static function meminfoKey(string $key): int
    {
        $map = self::meminfo();

        return $map[$key] ?? 0;
    }
}
