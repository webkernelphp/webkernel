<?php declare(strict_types=1);

namespace Webkernel\System\Dto;

use Webkernel\System\Contracts\Info\OpcacheInfoInterface;
use Webkernel\System\Support\ByteFormatter;

/**
 * Immutable OPcache snapshot.
 *
 * @internal
 */
final readonly class OpcacheInfo implements OpcacheInfoInterface
{
    public function __construct(
        private bool   $enabled,
        private ?float $hitRatio,
        private ?int   $cachedScripts,
        private ?int   $memoryUsed,
        private ?int   $memoryFree,
        private ?float $wastedPercentage,
    ) {}

    public function enabled(): bool
    {
        return $this->enabled;
    }

    public function hitRatio(): ?float
    {
        return $this->hitRatio;
    }

    public function cachedScripts(): ?int
    {
        return $this->cachedScripts;
    }

    public function memoryUsed(): ?int
    {
        return $this->memoryUsed;
    }

    public function memoryFree(): ?int
    {
        return $this->memoryFree;
    }

    public function wastedPercentage(): ?float
    {
        return $this->wastedPercentage;
    }

    public function humanMemoryUsed(): ?string
    {
        return $this->memoryUsed !== null ? ByteFormatter::format($this->memoryUsed) : null;
    }

    public function humanMemoryFree(): ?string
    {
        return $this->memoryFree !== null ? ByteFormatter::format($this->memoryFree) : null;
    }
}
