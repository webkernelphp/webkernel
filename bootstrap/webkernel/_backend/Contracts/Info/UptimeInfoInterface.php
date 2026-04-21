<?php declare(strict_types=1);

namespace Webkernel\System\Contracts\Info;

/**
 * Host uptime parsed from /proc/uptime.
 *
 * @api
 */
interface UptimeInfoInterface
{
    /**
     * Whether uptime data was successfully read.
     * False on shared hosting where /proc/uptime is inaccessible.
     */
    public function available(): bool;

    /** Total uptime in seconds. Returns 0 when /proc/uptime is unreadable. */
    public function seconds(): int;

    /** Human-readable uptime, e.g. "3d 4h 12m". Returns "" when unreadable. */
    public function human(): string;

    /** Days component. */
    public function days(): int;

    /** Hours component (0–23). */
    public function hours(): int;

    /** Minutes component (0–59). */
    public function minutes(): int;
}
