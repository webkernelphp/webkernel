<?php declare(strict_types=1);

namespace Webkernel\System\Contracts\Info;

/**
 * System process summary from /proc.
 *
 * @api
 */
interface ProcessInfoInterface
{
    /**
     * Total number of running processes on the host.
     * Returns 0 when /proc is unreadable or not on Linux.
     */
    public function count(): int;
}
