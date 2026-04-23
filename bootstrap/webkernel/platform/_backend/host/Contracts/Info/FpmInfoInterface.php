<?php declare(strict_types=1);

namespace Webkernel\System\Host\Contracts\Info;

/**
 * PHP-FPM worker pool metrics from /proc.
 *
 * @api
 */
interface FpmInfoInterface
{
    /**
     * Whether FPM workers were detected in /proc.
     * False on non-Linux or when /proc is unreadable.
     */
    public function available(): bool;

    /**
     * Currently active (running-state) FPM workers.
     * Returns null when not available.
     */
    public function active(): ?int;

    /**
     * Total FPM worker processes found.
     * Returns null when not available.
     */
    public function total(): ?int;

    /**
     * Worker pool saturation as 0–100 float.
     * Returns null when not available.
     */
    public function percentage(): ?float;
}
