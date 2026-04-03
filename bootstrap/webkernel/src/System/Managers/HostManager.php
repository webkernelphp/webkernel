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
use Webkernel\System\Support\ProcReader;

/**
 * Machine-level context metrics manager.
 *
 * Reads /proc on Linux or falls back to PHP built-ins on other OS families.
 * All DTOs are memoised after first construction within a single instance.
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

        [$load1, $load5, $load15] = ProcReader::loadavg();

        return $this->cpu = new CpuInfo(
            loadAvg1:  $load1,
            loadAvg5:  $load5,
            loadAvg15: $load15,
            cores:     ProcReader::cpuCores(),
        );
    }

    public function memory(): HostMemoryInfoInterface
    {
        if ($this->memory !== null) {
            return $this->memory;
        }

        $map = ProcReader::meminfo();

        return $this->memory = new HostMemoryInfo(
            total:     ($map['MemTotal']     ?? 0) * 1024,
            available: ($map['MemAvailable'] ?? 0) * 1024,
            cached:    ($map['Cached']       ?? 0) * 1024,
            buffers:   ($map['Buffers']      ?? 0) * 1024,
            swapTotal: ($map['SwapTotal']    ?? 0) * 1024,
            swapFree:  ($map['SwapFree']     ?? 0) * 1024,
        );
    }

    public function disk(): DiskInfoInterface
    {
        if ($this->disk !== null) {
            return $this->disk;
        }

        return $this->disk = new DiskInfo(
            path:  '/',
            total: (int) (@disk_total_space('/') ?: 0),
            free:  (int) (@disk_free_space('/')  ?: 0),
        );
    }

    public function uptime(): UptimeInfoInterface
    {
        return $this->uptime ??= new UptimeInfo(ProcReader::uptimeSeconds());
    }

    public function fpm(): FpmInfoInterface
    {
        if ($this->fpm !== null) {
            return $this->fpm;
        }

        [$active, $total] = ProcReader::fpmWorkers();

        return $this->fpm = new FpmInfo(
            available: $total !== null,
            active:    $active,
            total:     $total,
        );
    }

    public function processes(): ProcessInfoInterface
    {
        return $this->processes ??= new ProcessInfo(ProcReader::processCount());
    }

    public function entropy(): int
    {
        return ProcReader::entropyAvailable();
    }
}
