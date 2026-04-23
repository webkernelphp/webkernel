<?php declare(strict_types=1);

namespace Webkernel\System\Host\Dto;

use Webkernel\System\Host\Contracts\Info\CpuInfoInterface;

/**
 * Immutable CPU metrics snapshot.
 *
 * @internal
 */
final readonly class CpuInfo implements CpuInfoInterface
{
    public function __construct(
        private float $loadAvg1,
        private float $loadAvg5,
        private float $loadAvg15,
        private int   $cores,
        private bool  $dataAvailable = true,
    ) {}

    public static function unavailable(): self
    {
        return new self(0.0, 0.0, 0.0, 0, dataAvailable: false);
    }

    public function available(): bool
    {
        return $this->dataAvailable;
    }

    public function loadAvg1(): float
    {
        return $this->loadAvg1;
    }

    public function loadAvg5(): float
    {
        return $this->loadAvg5;
    }

    public function loadAvg15(): float
    {
        return $this->loadAvg15;
    }

    public function cores(): int
    {
        return $this->cores;
    }

    public function usage(): float
    {
        if ($this->cores === 0) {
            return 0.0;
        }

        return min(100.0, round($this->loadAvg1 / $this->cores * 100.0, 1));
    }
}
