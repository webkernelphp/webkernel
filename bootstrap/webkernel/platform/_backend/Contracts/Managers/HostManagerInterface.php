<?php declare(strict_types=1);

namespace Webkernel\System\Contracts\Managers;

use Webkernel\System\Contracts\Info\CpuInfoInterface;
use Webkernel\System\Contracts\Info\DiskInfoInterface;
use Webkernel\System\Contracts\Info\FpmInfoInterface;
use Webkernel\System\Contracts\Info\HostMemoryInfoInterface;
use Webkernel\System\Contracts\Info\ProcessInfoInterface;
use Webkernel\System\Contracts\Info\UptimeInfoInterface;

/**
 * Machine-level context metrics.
 *
 * SECONDARY data surface: shown as context alongside instance metrics.
 * Data is read from /proc (Linux) or PHP built-ins, never from shell_exec.
 *
 * @api
 */
interface HostManagerInterface
{
    /**
     * CPU load averages and core count.
     */
    public function cpu(): CpuInfoInterface;

    /**
     * System RAM (total, used, free, cached, buffers) and swap.
     */
    public function memory(): HostMemoryInfoInterface;

    /**
     * Root-partition disk usage. Additional mounts are not scanned at this level.
     */
    public function disk(): DiskInfoInterface;

    /**
     * Host uptime parsed from /proc/uptime.
     */
    public function uptime(): UptimeInfoInterface;

    /**
     * PHP-FPM worker pool saturation from /proc.
     */
    public function fpm(): FpmInfoInterface;

    /**
     * Total process count and basic process info from /proc.
     */
    public function processes(): ProcessInfoInterface;

    /**
     * Kernel entropy pool size in bits (from /proc/sys/kernel/random/entropy_avail).
     * Returns 0 when unreadable or not on Linux.
     */
    public function entropy(): int;
}
