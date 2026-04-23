<?php declare(strict_types=1);

namespace Webkernel\System\Host\Dto;

use Webkernel\System\Host\Contracts\Info\PhpLimitsInfoInterface;
use Webkernel\System\Host\Support\ByteFormatter;

/**
 * Immutable PHP ini runtime limits snapshot.
 *
 * @internal
 */
final readonly class PhpLimitsInfo implements PhpLimitsInfoInterface
{
    public function __construct(
        private int $maxExecutionTime,
        private int $uploadMaxFilesize,
        private int $postMaxSize,
        private int $maxInputVars,
    ) {}

    public function maxExecutionTime(): int
    {
        return $this->maxExecutionTime;
    }

    public function uploadMaxFilesize(): int
    {
        return $this->uploadMaxFilesize;
    }

    public function postMaxSize(): int
    {
        return $this->postMaxSize;
    }

    public function maxInputVars(): int
    {
        return $this->maxInputVars;
    }

    public function humanMaxExecutionTime(): string
    {
        return $this->maxExecutionTime === 0 ? '∞' : $this->maxExecutionTime . 's';
    }

    public function humanUploadMaxFilesize(): string
    {
        return ByteFormatter::format($this->uploadMaxFilesize);
    }

    public function humanPostMaxSize(): string
    {
        return ByteFormatter::format($this->postMaxSize);
    }
}
