<?php declare(strict_types=1);

namespace Webkernel\System\Host\Contracts\Managers;

use Webkernel\System\Host\Enums\OsFamily;

/**
 * Operating system identity.
 *
 * @api
 */
interface OsManagerInterface
{
    /** Typed OS family enum (Linux, Darwin, Windows …). */
    public function family(): OsFamily;

    /** Human-readable distro name, e.g. "Ubuntu 22.04.3 LTS". */
    public function name(): string;

    /** Whether the host is a Linux system. */
    public function isLinux(): bool;

    /** Whether the host is a Windows system. */
    public function isWindows(): bool;

    /** Whether the host is macOS / Darwin. */
    public function isDarwin(): bool;

    /** Machine architecture string, e.g. "x86_64" or "aarch64". */
    public function architecture(): string;

    /** Kernel release string from uname -r. */
    public function kernelRelease(): string;

    /** Network hostname. */
    public function hostname(): string;

    /** Whether /proc is readable on this host. */
    public function hasProcFs(): bool;

    /** Whether the FFI extension is available and ffi.enable allows it. */
    public function ffiAvailable(): bool;

    /**
     * FFI activation level.
     *
     * Returns one of:
     *   'all'      — ffi.enable=true  (all scripts can call FFI)
     *   'preload'  — ffi.enable=preload (only preloaded definitions available,
     *                system metrics readers cannot use arbitrary FFI calls)
     *   'disabled' — FFI extension missing or ffi.enable=false
     *
     * This matters for metrics: only 'all' lets readers call FFI at runtime.
     * 'preload' is NOT equivalent to 'all' for dynamic system metric reads.
     */
    public function ffiMode(): string;
}
