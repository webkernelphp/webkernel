<?php declare(strict_types=1);

namespace Webkernel\System\Managers;

use Webkernel\System\Contracts\Info\CpuInfoInterface;
use Webkernel\System\Contracts\Info\DiskInfoInterface;
use Webkernel\System\Contracts\Info\FpmInfoInterface;
use Webkernel\System\Contracts\Info\HostMemoryInfoInterface;
use Webkernel\System\Contracts\Info\ProcessInfoInterface;
use Webkernel\System\Contracts\Info\UptimeInfoInterface;
use Webkernel\System\Contracts\Managers\HostManagerInterface;
use Webkernel\System\Dto\CpuInfo;
use Webkernel\System\Dto\DiskInfo;
use Webkernel\System\Dto\FpmInfo;
use Webkernel\System\Dto\HostMemoryInfo;
use Webkernel\System\Dto\ProcessInfo;
use Webkernel\System\Dto\UptimeInfo;
use Webkernel\System\Support\CapabilityMap;
use Webkernel\System\Support\HostMetricsCache;
use Webkernel\System\Support\HostReadStrategy;
use Webkernel\System\Support\StaticDataCache;

/**
 * Machine-level context metrics manager.
 *
 * Three-tier read strategy:
 *   - CapabilityMap decides which reader to use (built once per worker)
 *   - HostMetricsCache (Tier B) caches slow-changing data to a file with TTL
 *   - Per-instance memo (Tier A shortcut) avoids repeated cache reads within a request
 *
 * On shared hosting where /proc is blocked and host_metrics_enabled = false,
 * all DTOs return available() = false and zero values — Blade checks isAvailable()
 * before rendering widgets.
 *
 * @internal  Resolved from the container. Type-hint HostManagerInterface.
 */
final class HostManager implements HostManagerInterface
{
    private ?CpuInfoInterface        $cpu       = null;
    private ?HostMemoryInfoInterface $memory    = null;
    private ?DiskInfoInterface       $disk      = null;
    private ?UptimeInfoInterface     $uptime    = null;
    private ?FpmInfoInterface        $fpm       = null;
    private ?ProcessInfoInterface    $processes = null;

    public function cpu(): CpuInfoInterface
    {
        if ($this->cpu !== null) {
            return $this->cpu;
        }

        $cap = CapabilityMap::get();

        if (!$cap->hostMetricsEnabled) {
            return $this->cpu = CpuInfo::unavailable();
        }

        return $this->cpu = HostMetricsCache::remember(
            'cpu',
            $cap->hostMetricsTtl,
            function () use ($cap): CpuInfo {
                [$load1, $load5, $load15] = HostReadStrategy::loadAvg();
                return new CpuInfo(
                    loadAvg1:      $load1,
                    loadAvg5:      $load5,
                    loadAvg15:     $load15,
                    cores:         StaticDataCache::remember('cpu.cores', fn() => HostReadStrategy::cpuCores()),
                    dataAvailable: true,
                );
            }
        );
    }

    public function memory(): HostMemoryInfoInterface
    {
        if ($this->memory !== null) {
            return $this->memory;
        }

        $cap = CapabilityMap::get();

        if (!$cap->hostMetricsEnabled) {
            return $this->memory = HostMemoryInfo::unavailable();
        }

        return $this->memory = HostMetricsCache::remember(
            'memory',
            $cap->hostMetricsTtl,
            function (): HostMemoryInfo {
                $map = HostReadStrategy::meminfo();
                if (empty($map)) {
                    return HostMemoryInfo::unavailable();
                }
                return new HostMemoryInfo(
                    total:         ($map['MemTotal']     ?? 0) * 1024,
                    available:     ($map['MemAvailable'] ?? 0) * 1024,
                    cached:        ($map['Cached']       ?? 0) * 1024,
                    buffers:       ($map['Buffers']      ?? 0) * 1024,
                    swapTotal:     ($map['SwapTotal']    ?? 0) * 1024,
                    swapFree:      ($map['SwapFree']     ?? 0) * 1024,
                    dataAvailable: true,
                );
            }
        );
    }

    public function disk(): DiskInfoInterface
    {
        if ($this->disk !== null) {
            return $this->disk;
        }

        $cap = CapabilityMap::get();

        return $this->disk = HostMetricsCache::remember(
            'disk',
            $cap->hostMetricsTtl,
            function (): DiskInfo {
                $stats = HostReadStrategy::diskStats('/');
                if ($stats['total'] === 0) {
                    return DiskInfo::unavailable();
                }
                return new DiskInfo(
                    path:          '/',
                    total:         $stats['total'],
                    free:          $stats['free'],
                    dataAvailable: true,
                );
            }
        );
    }

    public function uptime(): UptimeInfoInterface
    {
        if ($this->uptime !== null) {
            return $this->uptime;
        }

        $cap = CapabilityMap::get();

        if (!$cap->hostMetricsEnabled) {
            return $this->uptime = UptimeInfo::unavailable();
        }

        return $this->uptime = HostMetricsCache::remember(
            'uptime',
            $cap->hostMetricsTtl,
            function (): UptimeInfo {
                $seconds = HostReadStrategy::uptimeSeconds();
                return new UptimeInfo(
                    seconds:       $seconds,
                    dataAvailable: $seconds > 0,
                );
            }
        );
    }

    public function fpm(): FpmInfoInterface
    {
        if ($this->fpm !== null) {
            return $this->fpm;
        }

        $cap = CapabilityMap::get();

        return $this->fpm = HostMetricsCache::remember(
            'fpm',
            $cap->hostMetricsTtl,
            function (): FpmInfo {
                [$active, $total] = HostReadStrategy::fpmWorkers();
                return new FpmInfo(
                    available: $total !== null,
                    active:    $active,
                    total:     $total,
                );
            }
        );
    }

    public function processes(): ProcessInfoInterface
    {
        if ($this->processes !== null) {
            return $this->processes;
        }

        $cap = CapabilityMap::get();

        if (!$cap->processMetricsEnabled) {
            return $this->processes = ProcessInfo::unavailable();
        }

        return $this->processes = HostMetricsCache::remember(
            'processes',
            $cap->hostMetricsTtl,
            function (): ProcessInfo {
                $count = HostReadStrategy::processCount();
                return new ProcessInfo(
                    count:         $count,
                    dataAvailable: CapabilityMap::get()->hasProcFs,
                );
            }
        );
    }

    public function entropy(): int
    {
        return HostReadStrategy::entropyAvailable();
    }
}
