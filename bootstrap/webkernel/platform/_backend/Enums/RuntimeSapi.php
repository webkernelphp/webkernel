<?php declare(strict_types=1);

namespace Webkernel\System\Enums;

/**
 * PHP SAPI identifier.
 *
 * Covers the SAPIs relevant to web-kernel deployments.
 * The `value` is the raw PHP_SAPI string.
 */
enum RuntimeSapi: string
{
    case Fpm       = 'fpm-fcgi';
    case Cli       = 'cli';
    case CliServer = 'cli-server';
    case Apache    = 'apache2handler';
    case Swoole    = 'swoole';
    case RoadRunner = 'roadrunner';
    case FrankenPHP = 'frankenphp';
    case Unknown   = 'unknown';

    /**
     * Resolve from PHP_SAPI.
     */
    public static function current(): self
    {
        return self::tryFrom(PHP_SAPI) ?? self::Unknown;
    }

    public function isFpm(): bool
    {
        return $this === self::Fpm;
    }

    public function isCli(): bool
    {
        return $this === self::Cli || $this === self::CliServer;
    }

    /**
     * True for async runtimes that reuse workers across requests.
     * $_SERVER is NOT a plain array under these runtimes — use request()->server().
     */
    public function isAsync(): bool
    {
        return match ($this) {
            self::Swoole, self::RoadRunner, self::FrankenPHP => true,
            default => false,
        };
    }
}
