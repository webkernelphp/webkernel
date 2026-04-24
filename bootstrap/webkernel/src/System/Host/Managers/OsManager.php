<?php declare(strict_types=1);

namespace Webkernel\System\Host\Managers;

use Webkernel\System\Host\Enums\OsFamily;
use Webkernel\System\Host\Services\OsDetectionService;
use Webkernel\System\Host\Contracts\Managers\OsManagerInterface;

/**
 * Operating system identity manager.
 *
 * Delegates to OsDetectionService which is already registered as a
 * singleton and caches the OsInfo DTO for the worker lifetime.
 *
 * @internal  Resolved from the container. Type-hint OsManagerInterface.
 */
final class OsManager implements OsManagerInterface
{
    public function __construct(private readonly OsDetectionService $detection) {}

    public function family(): OsFamily
    {
        return $this->detection->detect()->family;
    }

    public function name(): string
    {
        return $this->detection->detect()->distroName;
    }

    public function isLinux(): bool
    {
        return $this->detection->detect()->isLinux();
    }

    public function isWindows(): bool
    {
        return $this->detection->detect()->family === OsFamily::Windows;
    }

    public function isDarwin(): bool
    {
        return $this->detection->detect()->family === OsFamily::Darwin;
    }

    public function architecture(): string
    {
        return $this->detection->detect()->architecture;
    }

    public function kernelRelease(): string
    {
        return $this->detection->detect()->kernelRelease;
    }

    public function hostname(): string
    {
        return $this->detection->detect()->hostname;
    }

    public function hasProcFs(): bool
    {
        return $this->detection->detect()->hasProcFs;
    }

    public function ffiAvailable(): bool
    {
        return $this->detection->detect()->ffiAvailable;
    }

    /**
     * FFI activation level: 'all' | 'preload' | 'disabled'.
     *
     * 'preload' is NOT the same as 'all': dynamic FFI calls (needed for
     * runtime system metric reads) only work when ffi.enable=true ('all').
     * Under 'preload', only definitions loaded via ffi.preload are usable.
     */
    public function ffiMode(): string
    {
        if (! extension_loaded('ffi')) {
            return 'disabled';
        }

        $setting = strtolower(trim((string) ini_get('ffi.enable')));

        return match ($setting) {
            'true', '1', 'on', 'yes' => 'all',
            'preload'                 => 'preload',
            default                   => 'disabled',
        };
    }
}
