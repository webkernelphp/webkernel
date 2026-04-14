<?php declare(strict_types=1);

namespace Webkernel\System\Contracts\Managers;

use Webkernel\System\Enums\RuntimeSapi;

/**
 * PHP runtime context (SAPI, server adapter).
 *
 * @api
 */
interface RuntimeManagerInterface
{
    /** Typed SAPI enum. */
    public function sapi(): RuntimeSapi;

    /** True when running under PHP-FPM. */
    public function isFpm(): bool;

    /** True when running from CLI or cli-server. */
    public function isCli(): bool;

    /** True when running under Swoole or RoadRunner. */
    public function isAsync(): bool;

    /** SERVER_SOFTWARE value, null when not set (CLI). */
    public function serverSoftware(): ?string;

    /** SERVER_ADDR value, null when not set. */
    public function serverAddress(): ?string;

    /** SERVER_PORT value, null when not set. */
    public function serverPort(): ?int;
}
