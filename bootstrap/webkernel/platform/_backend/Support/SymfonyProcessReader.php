<?php declare(strict_types=1);

namespace Webkernel\System\Support;

/**
 * Optional fallback reader using Symfony Process.
 *
 * Used when /proc is not directly readable (security policy, open_basedir)
 * but shell_exec / proc_open are allowed and symfony/process is installed.
 *
 * All methods are safe to call without the package installed — they return
 * graceful zero-value stubs when Process is unavailable.
 *
 * symfony/process is a suggested dependency, not required.
 */
final class SymfonyProcessReader
{
    public static function loadavg(): array
    {
        $out = self::run(['cat', '/proc/loadavg']);

        if ($out === null) {
            $la = @sys_getloadavg() ?: [0.0, 0.0, 0.0];
            return [(float) $la[0], (float) $la[1], (float) $la[2]];
        }

        $parts = explode(' ', trim($out));
        return [
            (float) ($parts[0] ?? 0.0),
            (float) ($parts[1] ?? 0.0),
            (float) ($parts[2] ?? 0.0),
        ];
    }

    public static function cpuCores(): int
    {
        $out = self::run(['nproc']);
        return $out !== null ? max(1, (int) trim($out)) : 1;
    }

    public static function diskStats(string $path = '/'): array
    {
        $out = self::run(['df', '-B1', $path]);

        if ($out === null) {
            return ['total' => 0, 'free' => 0];
        }

        $lines = array_filter(explode("\n", trim($out)));
        $line  = end($lines);
        $parts = preg_split('/\s+/', (string) $line);

        return [
            'total' => (int) ($parts[1] ?? 0),
            'free'  => (int) ($parts[3] ?? 0),
        ];
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private static function run(array $command): ?string
    {
        if (!class_exists(\Symfony\Component\Process\Process::class)) {
            return null;
        }

        try {
            $proc = new \Symfony\Component\Process\Process($command);
            $proc->setTimeout(1.0);
            $proc->run();
            return $proc->isSuccessful() ? $proc->getOutput() : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
