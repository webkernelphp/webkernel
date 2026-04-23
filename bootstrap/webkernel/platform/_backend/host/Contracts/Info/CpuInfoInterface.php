<?php declare(strict_types=1);

namespace Webkernel\System\Host\Contracts\Info;

/**
 * Host CPU metrics.
 *
 * @api
 */
interface CpuInfoInterface
{
    /**
     * Whether CPU metrics were successfully read.
     * False on shared hosting where /proc and shell_exec are both unavailable.
     */
    public function available(): bool;

    /** 1-minute load average. */
    public function loadAvg1(): float;

    /** 5-minute load average. */
    public function loadAvg5(): float;

    /** 15-minute load average. */
    public function loadAvg15(): float;

    /** Total logical CPU cores. */
    public function cores(): int;

    /**
     * CPU usage as 0–100 float derived from loadAvg1 / cores * 100.
     * Capped at 100.0.
     */
    public function usage(): float;
}
