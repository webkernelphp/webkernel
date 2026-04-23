<?php declare(strict_types=1);

namespace Webkernel\System\Support;

/**
 * Converts raw byte counts to human-readable strings and back.
 *
 * Handles php.ini shorthand notation (128M, 1G, 512K, -1, unlimited)
 * and formats integers as compact human-readable strings.
 *
 * All methods are pure functions with no side effects.
 */
final class ByteFormatter
{
    private const UNITS = ['B', 'KB', 'MB', 'GB', 'TB'];

    /**
     * Convert a php.ini shorthand size string to bytes.
     *
     * Returns -1 for "unlimited" / "-1" / empty string.
     * Returns 0 for "0".
     */
    public static function parse(string $value): int
    {
        $value = trim($value);

        if ($value === '' || $value === '-1' || strtolower($value) === 'unlimited') {
            return -1;
        }

        $numeric = (int) $value;
        $suffix  = strtoupper(substr($value, -1));

        return match ($suffix) {
            'G'     => $numeric * 1_073_741_824,
            'M'     => $numeric * 1_048_576,
            'K'     => $numeric * 1_024,
            default => $numeric,
        };
    }

    /**
     * Format a raw byte count as a human-readable string.
     *
     * Special cases:
     *   -1  => "∞"
     *    0  => "0 B"
     * PHP_INT_MAX => "∞"
     */
    public static function format(int $bytes): string
    {
        if ($bytes < 0 || $bytes === PHP_INT_MAX) {
            return '∞';
        }

        if ($bytes === 0) {
            return '0 B';
        }

        $i = min((int) floor(log($bytes, 1024)), count(self::UNITS) - 1);

        return round($bytes / (1024 ** $i), 2) . ' ' . self::UNITS[$i];
    }
}
