<?php declare(strict_types=1);

namespace Webkernel\System\Access\Contracts\Managers;

/**
 * Laravel application configuration surface.
 *
 * Values are read from the booted Laravel container — no file I/O.
 *
 * @api
 */
interface AppManagerInterface
{
    /** Laravel environment string (production, local, staging …). */
    public function environment(): string;

    /** Whether APP_DEBUG is truthy. */
    public function debug(): bool;

    /** Whether the current environment is production. */
    public function isProduction(): bool;

    /** Default cache store name (e.g. "redis", "file"). */
    public function cacheDriver(): string;

    /** Default queue connection name (e.g. "redis", "sync"). */
    public function queueDriver(): string;

    /** Application timezone string. */
    public function timezone(): string;

    /** Application URL from config. */
    public function url(): string;

    /** Laravel framework version string. */
    public function laravelVersion(): string;
}
