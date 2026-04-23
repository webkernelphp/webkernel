<?php declare(strict_types=1);

namespace Webkernel\System\Host\Contracts\Info;

/**
 * Host system memory metrics (RAM + swap).
 *
 * @api
 */
interface HostMemoryInfoInterface
{
    /**
     * Whether host memory data was successfully read.
     * False on shared hosting where /proc is blocked.
     * Check this before rendering metric widgets.
     *
     * Note: named isAvailable() to avoid collision with available(): int below.
     */
    public function isAvailable(): bool;

    // -- RAM ------------------------------------------------------------------

    /** Total physical RAM in bytes. */
    public function total(): int;

    /** Available RAM in bytes (MemAvailable from /proc/meminfo). */
    public function available(): int;

    /** Used RAM in bytes (total − available). */
    public function used(): int;

    /** RAM usage as 0–100 float. */
    public function percentage(): float;

    /** Cached memory in bytes from /proc/meminfo. Returns 0 when unreadable. */
    public function cached(): int;

    /** Buffer memory in bytes from /proc/meminfo. Returns 0 when unreadable. */
    public function buffers(): int;

    /** Human-readable used RAM, e.g. "3.2 GB". */
    public function humanUsed(): string;

    /** Human-readable total RAM. */
    public function humanTotal(): string;

    // -- Swap -----------------------------------------------------------------

    /** Total swap space in bytes. Returns 0 when no swap partition exists. */
    public function swapTotal(): int;

    /** Free swap space in bytes. */
    public function swapFree(): int;

    /** Used swap in bytes. */
    public function swapUsed(): int;

    /**
     * Swap usage as 0–100 float.
     * Returns 0.0 when no swap exists.
     */
    public function swapPercentage(): float;

    /** Human-readable swap used. Returns "0 B" when no swap. */
    public function humanSwapUsed(): string;

    /** Human-readable swap total. Returns "0 B" when no swap. */
    public function humanSwapTotal(): string;

    /** True when a swap partition / swap file is present. */
    public function hasSwap(): bool;
}
