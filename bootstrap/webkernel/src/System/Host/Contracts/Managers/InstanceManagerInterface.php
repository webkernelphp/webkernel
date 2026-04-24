<?php declare(strict_types=1);

namespace Webkernel\System\Host\Contracts\Managers;

use Webkernel\System\Host\Contracts\Info\InstanceMemoryInfoInterface;
use Webkernel\System\Host\Contracts\Info\OpcacheInfoInterface;
use Webkernel\System\Host\Contracts\Info\PhpInfoInterface;
use Webkernel\System\Host\Contracts\Info\PhpLimitsInfoInterface;

/**
 * PHP process-level metrics.
 *
 * PRIMARY data surface: what this process uses vs what it is allowed.
 * All values reflect the current worker/request, not the host machine.
 *
 * @api
 */
interface InstanceManagerInterface
{
    /**
     * PHP process memory: used, peak, limit, headroom.
     */
    public function memory(): InstanceMemoryInfoInterface;

    /**
     * OPcache status: enabled, hit ratio, memory, cached script count.
     */
    public function opcache(): OpcacheInfoInterface;

    /**
     * PHP build identity: version, SAPI, ini file path, extension count.
     */
    public function php(): PhpInfoInterface;

    /**
     * PHP runtime ini limits: max_execution_time, upload, post, max_input_vars.
     */
    public function limits(): PhpLimitsInfoInterface;

    /**
     * Current Laravel environment string (production, local, staging …).
     */
    public function environment(): string;

    /**
     * Whether APP_DEBUG is truthy in the current environment.
     */
    public function debug(): bool;
}
