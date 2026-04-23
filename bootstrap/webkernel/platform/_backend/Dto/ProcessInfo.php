<?php declare(strict_types=1);

namespace Webkernel\System\Dto;

use Webkernel\System\Contracts\Info\ProcessInfoInterface;

/**
 * Immutable process count snapshot.
 *
 * @internal
 */
final readonly class ProcessInfo implements ProcessInfoInterface
{
    public function __construct(
        private int  $count,
        private bool $dataAvailable = true,
    ) {}

    public static function unavailable(): self
    {
        return new self(0, dataAvailable: false);
    }

    public function available(): bool
    {
        return $this->dataAvailable;
    }

    public function count(): int
    {
        return $this->count;
    }
}
