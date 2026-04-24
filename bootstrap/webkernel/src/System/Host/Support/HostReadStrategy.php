<?php declare(strict_types=1);

namespace Webkernel\System\Host\Support;

/**
 * Fallback-chain resolver for host metric reads.
 *
 * Priority per metric:
 *   1. ProcReader      — direct /proc reads, fastest, Linux only
 *   2. SymfonyProcess  — subprocess (cat /proc, df, nproc), ~5ms, needs proc_open
 *   3. PHP builtins    — sys_getloadavg, disk_*_space — always available
 *   4. Graceful stub   — returns zero / empty, never throws
 *
 * Which reader is used is decided once per worker by CapabilityMap::get().
 * No per-request re-evaluation.
 */
final class HostReadStrategy
{
    public static function loadAvg(): array
    {
        $cap = CapabilityMap::get();

        if ($cap->hasProcFs) {
            return ProcReader::loadavg();
        }

        if ($cap->hasSymfonyProcess) {
            return SymfonyProcessReader::loadavg();
        }

        $la = @sys_getloadavg() ?: [0.0, 0.0, 0.0];
        return [(float) $la[0], (float) $la[1], (float) $la[2]];
    }

    public static function meminfo(): array
    {
        $cap = CapabilityMap::get();

        if ($cap->hasProcFs) {
            return ProcReader::meminfo();
        }

        return [];  // Unavailable — callers must check for empty array
    }

    public static function cpuCores(): int
    {
        $cap = CapabilityMap::get();

        if ($cap->hasProcFs) {
            return ProcReader::cpuCores();
        }

        if ($cap->hasSymfonyProcess) {
            return SymfonyProcessReader::cpuCores();
        }

        return 1;
    }

    public static function uptimeSeconds(): int
    {
        $cap = CapabilityMap::get();

        if ($cap->hasProcFs) {
            return ProcReader::uptimeSeconds();
        }

        return 0;
    }

    public static function diskStats(string $path = '/'): array
    {
        // disk_total_space() and disk_free_space() work on all platforms
        return [
            'total' => (int) (@disk_total_space($path) ?: 0),
            'free'  => (int) (@disk_free_space($path)  ?: 0),
        ];
    }

    /**
     * @return array{?int, ?int}  [activeWorkers, totalWorkers] or [null, null] when unavailable
     */
    public static function fpmWorkers(): array
    {
        $cap = CapabilityMap::get();

        if (!$cap->fpmMetricsEnabled) {
            return [null, null];
        }

        if ($cap->hasProcFs) {
            [$active, $total] = ProcReader::fpmWorkers();
            // ProcReader returns [0, 0] when nothing found — treat that as available with 0 workers
            return [$active, $total];
        }

        return [null, null];
    }

    public static function processCount(): int
    {
        $cap = CapabilityMap::get();

        if (!$cap->processMetricsEnabled) {
            return 0;
        }

        if ($cap->hasProcFs) {
            return ProcReader::processCount();
        }

        return 0;
    }

    public static function entropyAvailable(): int
    {
        if (CapabilityMap::get()->hasProcFs) {
            return ProcReader::entropyAvailable();
        }

        return 0;
    }
}
