<?php declare(strict_types=1);

namespace Webkernel\System\Contracts\Info;

/**
 * PHP process memory values for the current worker request.
 *
 * @api
 */
interface InstanceMemoryInfoInterface
{
    /** Bytes currently allocated by this PHP process (real allocation). */
    public function used(): int;

    /** Peak bytes allocated since process start. */
    public function peak(): int;

    /**
     * memory_limit in bytes.
     * Returns -1 when the limit is set to "unlimited" or "-1".
     */
    public function limit(): int;

    /**
     * Usage as 0–100 float.
     * Returns null when the limit is unlimited.
     */
    /**
     * Percentage of PHP memory limit consumed (0–100).
     * Returns 0.0 when memory_limit is -1 (unlimited).
     */
    public function percentage(): float;

    /**
     * Remaining bytes before the limit is hit.
     * Returns PHP_INT_MAX when the limit is unlimited.
     */
    public function headroom(): int;

    /** Human-readable used value, e.g. "12.5 MB". */
    public function humanUsed(): string;

    /** Human-readable peak value. */
    public function humanPeak(): string;

    /** Human-readable limit value. Returns "∞" when unlimited. */
    public function humanLimit(): string;

    /** True when memory_limit is -1 (no ceiling enforced). */
    public function isUnlimited(): bool;
}
