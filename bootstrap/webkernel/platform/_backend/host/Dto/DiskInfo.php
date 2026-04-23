<?php declare(strict_types=1);

namespace Webkernel\System\Host\Dto;

use Webkernel\System\Host\Contracts\Info\DiskInfoInterface;
use Webkernel\System\Host\Support\ByteFormatter;

/**
 * Immutable root disk usage snapshot.
 *
 * @internal
 */
final readonly class DiskInfo implements DiskInfoInterface
{
    public function __construct(
        private string $path,
        private int    $total,
        private int    $free,
        private bool   $dataAvailable = true,
    ) {}

    public static function unavailable(): self
    {
        return new self('/', 0, 0, dataAvailable: false);
    }

    public function available(): bool
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

    public function path(): string
    {
        return $this->path;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function free(): int
    {
        return $this->free;
    }

    public function used(): int
    {
        return max(0, $this->total - $this->free);
    }

    public function percentage(): float
    {
        if ($this->total === 0) {
            return 0.0;
        }

        return min(100.0, round($this->used() / $this->total * 100.0, 1));
    }

    public function humanUsed(): string
    {
        return ByteFormatter::format($this->used());
    }

    public function humanTotal(): string
    {
        return ByteFormatter::format($this->total);
    }

    public function humanFree(): string
    {
        return ByteFormatter::format($this->free);
    }
}
