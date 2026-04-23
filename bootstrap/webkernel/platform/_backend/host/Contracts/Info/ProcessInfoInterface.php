<?php declare(strict_types=1);

namespace Webkernel\System\Host\Contracts\Info;

/**
 * System process summary from /proc.
 *
 * @api
 */
interface ProcessInfoInterface
{
    /**
     * Whether process count data was successfully read.
     * False on shared hosting where /proc is inaccessible.
     */
    public function available(): bool;

    /**
     * Total number of running processes on the host.
     * Returns 0 when /proc is unreadable or not on Linux.
     */
    public function count(): int;
}
