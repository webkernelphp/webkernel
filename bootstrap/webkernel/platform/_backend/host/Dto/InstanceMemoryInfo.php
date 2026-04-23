<?php declare(strict_types=1);

namespace Webkernel\System\Host\Dto;

use Webkernel\System\Host\Contracts\Info\InstanceMemoryInfoInterface;
use Webkernel\System\Host\Support\ByteFormatter;

/**
 * Immutable PHP process memory snapshot.
 *
 * @internal  Produced by InstanceManager. Use the interface for type hints.
 */
final readonly class InstanceMemoryInfo implements InstanceMemoryInfoInterface
{
    public function __construct(
        private int $phpMemoryUsed,
        private int $phpMemoryPeak,
        private int $phpMemoryLimit,
    ) {}

    public function used(): int
    {
        return $this->phpMemoryUsed;
    }

    public function peak(): int
    {
        return $this->phpMemoryPeak;
    }

    public function limit(): int
    {
        return $this->phpMemoryLimit;
    }

    public function percentage(): float
    {
        // memory_limit = -1 means unlimited — report 0% usage (no ceiling to fill)
        if ($this->phpMemoryLimit <= 0) {
            return 0.0;
        }

        return min(100.0, round($this->phpMemoryUsed / $this->phpMemoryLimit * 100.0, 1));
    }

    public function headroom(): int
    {
        if ($this->phpMemoryLimit <= 0) {
            return PHP_INT_MAX;
        }

        return max(0, $this->phpMemoryLimit - $this->phpMemoryUsed);
    }

    public function humanUsed(): string
    {
        return ByteFormatter::format($this->phpMemoryUsed);
    }

    public function humanPeak(): string
    {
        return ByteFormatter::format($this->phpMemoryPeak);
    }

    public function humanLimit(): string
    {
        return ByteFormatter::format($this->phpMemoryLimit);
    }

    public function isUnlimited(): bool
    {
        return $this->phpMemoryLimit === -1;
    }
}
