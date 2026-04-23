<?php declare(strict_types=1);

namespace Webkernel\System\Host\Dto;

use Webkernel\System\Host\Enums\OsFamily;

/**
 * Immutable OS identity snapshot.
 *
 * Built once per worker by OsDetectionService and memoised.
 * All fields are populated at construction — no lazy reads.
 *
 * @internal  Produced by OsDetectionService. Type-hint OsManagerInterface.
 */
final readonly class OsInfo
{
    public function __construct(
        public readonly OsFamily $family,
        /** Human-readable distro name, e.g. "Ubuntu 22.04.3 LTS". */
        public readonly string   $distroName,
        /** uname -r output, e.g. "6.1.0-21-amd64". */
        public readonly string   $kernelRelease,
        /** uname -m output, e.g. "x86_64". */
        public readonly string   $architecture,
        /** System hostname. */
        public readonly string   $hostname,
        /** Whether /proc is readable (Linux only). */
        public readonly bool     $hasProcFs,
        /** Whether FFI extension is loaded and ffi.enable != false. */
        public readonly bool     $ffiAvailable,
    ) {}

    public function isLinux(): bool
    {
        return $this->family->isLinux();
    }
}
