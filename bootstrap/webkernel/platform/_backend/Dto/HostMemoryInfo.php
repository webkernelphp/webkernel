<?php declare(strict_types=1);

namespace Webkernel\System\Dto;

use Webkernel\System\Contracts\Info\HostMemoryInfoInterface;
use Webkernel\System\Support\ByteFormatter;

/**
 * Immutable host system memory snapshot.
 *
 * @internal
 */
final readonly class HostMemoryInfo implements HostMemoryInfoInterface
{
    public function __construct(
        private int  $total,
        private int  $available,
        private int  $cached,
        private int  $buffers,
        private int  $swapTotal,
        private int  $swapFree,
        private bool $dataAvailable = true,
    ) {}

    public static function unavailable(): self
    {
        return new self(0, 0, 0, 0, 0, 0, dataAvailable: false);
    }

    public function isAvailable(): bool
    {
        return $this->dataAvailable;
    }

    public function humanUsedOrUnavailable(string $fallback = '—'): string
    {
        return $this->dataAvailable ? $this->humanUsed() : $fallback;
    }

    public function humanTotalOrUnavailable(string $fallback = '—'): string
    {
        return $this->dataAvailable ? $this->humanTotal() : $fallback;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function available(): int
    {
        return $this->available;
    }

    public function used(): int
    {
        return max(0, $this->total - $this->available);
    }

    public function percentage(): float
    {
        if ($this->total === 0) {
            return 0.0;
        }

        return min(100.0, round($this->used() / $this->total * 100.0, 1));
    }

    public function cached(): int
    {
        return $this->cached;
    }

    public function buffers(): int
    {
        return $this->buffers;
    }

    public function humanUsed(): string
    {
        return ByteFormatter::format($this->used());
    }

    public function humanTotal(): string
    {
        return ByteFormatter::format($this->total);
    }

    public function swapTotal(): int
    {
        return $this->swapTotal;
    }

    public function swapFree(): int
    {
        return $this->swapFree;
    }

    public function swapUsed(): int
    {
        return max(0, $this->swapTotal - $this->swapFree);
    }

    public function swapPercentage(): float
    {
        if ($this->swapTotal === 0) {
            return 0.0;
        }

        return min(100.0, round($this->swapUsed() / $this->swapTotal * 100.0, 1));
    }

    public function humanSwapUsed(): string
    {
        return ByteFormatter::format($this->swapUsed());
    }

    public function humanSwapTotal(): string
    {
        return ByteFormatter::format($this->swapTotal);
    }

    public function hasSwap(): bool
    {
        return $this->swapTotal > 0;
    }
}
