<?php declare(strict_types=1);

namespace Webkernel\System\Host\Dto;

use Webkernel\System\Host\Contracts\Info\FpmInfoInterface;

/**
 * Immutable PHP-FPM worker pool snapshot.
 *
 * @internal
 */
final readonly class FpmInfo implements FpmInfoInterface
{
    public function __construct(
        private bool $available,
        private ?int $active,
        private ?int $total,
    ) {}

    public function available(): bool
    {
        return $this->available;
    }

    public function active(): ?int
    {
        return $this->active;
    }

    public function total(): ?int
    {
        return $this->total;
    }

    public function percentage(): ?float
    {
        if ($this->total === null || $this->total === 0) {
            return null;
        }

        return min(100.0, round(($this->active ?? 0) / $this->total * 100.0, 1));
    }
}
