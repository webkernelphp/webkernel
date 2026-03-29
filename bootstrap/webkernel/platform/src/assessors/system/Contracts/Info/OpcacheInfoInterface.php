<?php declare(strict_types=1);

namespace Webkernel\System\Contracts\Info;

/**
 * OPcache extension status and memory metrics.
 *
 * @api
 */
interface OpcacheInfoInterface
{
    /** Whether the OPcache extension is loaded and opcache.enable=1. */
    public function enabled(): bool;

    /**
     * Hit ratio as 0.0–100.0 float.
     * Returns null when OPcache is disabled or stats unavailable.
     */
    public function hitRatio(): ?float;

    /**
     * Number of scripts cached in shared memory.
     * Returns null when OPcache is disabled.
     */
    public function cachedScripts(): ?int;

    /**
     * Shared memory bytes currently used by OPcache.
     * Returns null when OPcache is disabled.
     */
    public function memoryUsed(): ?int;

    /**
     * Shared memory bytes free in OPcache.
     * Returns null when OPcache is disabled.
     */
    public function memoryFree(): ?int;

    /**
     * Wasted memory percentage (fragmentation indicator).
     * Returns null when OPcache is disabled.
     */
    public function wastedPercentage(): ?float;

    /** Human-readable used memory, e.g. "64 MB". Null when disabled. */
    public function humanMemoryUsed(): ?string;

    /** Human-readable free memory. Null when disabled. */
    public function humanMemoryFree(): ?string;
}
