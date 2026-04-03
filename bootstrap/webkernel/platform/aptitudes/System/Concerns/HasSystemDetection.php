<?php declare(strict_types=1);

namespace Webkernel\Aptitudes\System\Concerns;

use Webkernel\Contracts\Managers\HostManagerInterface;
use Webkernel\Contracts\Managers\InstanceManagerInterface;
use Webkernel\Contracts\Managers\OsManagerInterface;

/**
 * Convenience accessors for consuming system info in Livewire page components.
 *
 * Provides typed, pre-resolved manager instances via the webkernel() API.
 * Mix into a Livewire component for zero-boilerplate access.
 */
trait HasSystemDetection
{
    /**
     * Resolve the instance manager (PHP process metrics).
     */
    protected function instanceMetrics(): InstanceManagerInterface
    {
        return webkernel()->instance();
    }

    /**
     * Resolve the host manager (machine context metrics).
     */
    protected function hostMetrics(): HostManagerInterface
    {
        return webkernel()->host();
    }

    /**
     * Resolve the OS manager (identity, capabilities).
     */
    protected function osInfo(): OsManagerInterface
    {
        return webkernel()->os();
    }

    /**
     * True when the FFI reader is available (non-degraded mode).
     */
    protected function isFfiMode(): bool
    {
        return webkernel()->os()->ffiAvailable();
    }

    /**
     * True when running on a Linux host with a readable /proc filesystem.
     */
    protected function isLinuxProcFs(): bool
    {
        return webkernel()->os()->isLinux() && webkernel()->os()->hasProcFs();
    }
}
