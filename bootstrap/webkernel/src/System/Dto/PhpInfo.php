<?php declare(strict_types=1);

namespace Webkernel\System\Dto;

use Webkernel\System\Contracts\Info\PhpInfoInterface;

/**
 * Immutable PHP build identity snapshot.
 *
 * @internal
 */
final readonly class PhpInfo implements PhpInfoInterface
{
    public function __construct(
        private string  $version,
        private string  $sapi,
        private ?string $iniFile,
        private int     $extensionCount,
    ) {}

    public function version(): string
    {
        return $this->version;
    }

    public function sapi(): string
    {
        return $this->sapi;
    }

    public function iniFile(): ?string
    {
        return $this->iniFile;
    }

    public function extensionCount(): int
    {
        return $this->extensionCount;
    }

    public function hasExtension(string $name): bool
    {
        return extension_loaded($name);
    }
}
