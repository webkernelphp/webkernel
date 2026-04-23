<?php declare(strict_types=1);

namespace Webkernel\System\Host\Contracts\Info;

/**
 * Disk usage for the root mount point.
 *
 * @api
 */
interface DiskInfoInterface
{
    /**
     * Whether disk metrics were successfully read.
     * False when disk_total_space() is blocked by open_basedir or returns 0.
     */
    public function available(): bool;

    /** Mount path measured (always "/" at this level). */
    public function path(): string;

    /** Total disk space in bytes. */
    public function total(): int;

    /** Free disk space in bytes. */
    public function free(): int;

    /** Used disk space in bytes (total − free). */
    public function used(): int;

    /** Disk usage as 0–100 float. */
    public function percentage(): float;

    /** Human-readable used space, e.g. "48.3 GB". */
    public function humanUsed(): string;

    /** Human-readable total space. */
    public function humanTotal(): string;

    /** Human-readable free space. */
    public function humanFree(): string;
}
