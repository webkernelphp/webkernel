<?php declare(strict_types=1);

namespace Webkernel\System\Dto;

use Webkernel\System\Contracts\Info\UptimeInfoInterface;

/**
 * Immutable host uptime snapshot.
 *
 * @internal
 */
final readonly class UptimeInfo implements UptimeInfoInterface
{
    public function __construct(
        private int  $seconds,
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

    public function seconds(): int
    {
        return $this->seconds;
    }

    public function days(): int
    {
        return (int) floor($this->seconds / 86400);
    }

    public function hours(): int
    {
        return (int) floor(($this->seconds % 86400) / 3600);
    }

    public function minutes(): int
    {
        return (int) floor(($this->seconds % 3600) / 60);
    }

    public function human(): string
    {
        if ($this->seconds <= 0) {
            return '';
        }

        $parts = [];

        if ($this->days() > 0) {
            $parts[] = $this->days() . 'd';
        }

        if ($this->hours() > 0) {
            $parts[] = $this->hours() . 'h';
        }

        $parts[] = $this->minutes() . 'm';

        return implode(' ', $parts);
    }
}
