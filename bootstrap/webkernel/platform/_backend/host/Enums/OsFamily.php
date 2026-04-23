<?php declare(strict_types=1);

namespace Webkernel\System\Host\Enums;

/**
 * Operating system family.
 *
 * Matches PHP_OS_FAMILY values so direct comparison is always safe.
 */
enum OsFamily: string
{
    case Linux   = 'Linux';
    case Darwin  = 'Darwin';
    case Windows = 'Windows';
    case BSD     = 'BSD';
    case Solaris = 'Solaris';
    case Unknown = 'Unknown';

    /**
     * Resolve from PHP_OS_FAMILY (or any uname output).
     */
    public static function current(): self
    {
        return self::tryFrom(PHP_OS_FAMILY) ?? self::Unknown;
    }

    public function isLinux(): bool
    {
        return $this === self::Linux;
    }

    public function isDarwin(): bool
    {
        return $this === self::Darwin;
    }

    public function isWindows(): bool
    {
        return $this === self::Windows;
    }

    public function hasProcFs(): bool
    {
        return $this === self::Linux;
    }
}
