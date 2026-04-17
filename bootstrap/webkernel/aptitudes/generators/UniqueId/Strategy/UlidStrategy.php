<?php

declare(strict_types=1);

namespace Webkernel\Generators\UniqueId\Strategy;

/**
 * ULID — Universally Unique Lexicographically Sortable Identifier.
 *
 * 128-bit identifier: 48-bit millisecond timestamp + 80-bit random.
 * Encoded in Crockford Base32 (uppercase, always 26 chars).
 * Sortable, monotonic within the same millisecond, URL-safe.
 *
 * Length parameter is ignored (ULIDs are always 26 chars).
 *
 * Options:
 *   lowercase  bool  Return lowercase (default: false)
 *
 * @example
 *   $id = (new UlidStrategy)->generate();
 *   // → "01ARZ3NDEKTSV4RRFFQ69G5FAV"
 */
final class UlidStrategy extends AbstractStrategy
{
    /** Crockford Base32 alphabet (no I, L, O, U — avoids ambiguity) */
    private const ENCODING = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    public static function name(): string
    {
        return 'ulid';
    }

    public static function description(): string
    {
        return 'ULID: 48-bit timestamp + 80-bit random, Crockford Base32 encoded. Time-sortable.';
    }

    public function generate(int $length = 26, array $options = []): string
    {
        $lowercase = (bool) ($options['lowercase'] ?? false);

        $msec = (int) (microtime(true) * 1000);

        // 10 chars for timestamp (48 bits → 10 × 5-bit Crockford chars)
        $timeStr = '';
        for ($i = 9; $i >= 0; $i--) {
            $timeStr = self::ENCODING[$msec & 0x1f] . $timeStr;
            $msec    = $msec >> 5;
        }

        // 16 chars for randomness (80 bits → 16 × 5-bit chars)
        $randomBytes = random_bytes(10); // 80 bits
        $randomStr   = '';
        $bits        = '';

        // Unpack bytes into binary string, then group into 5-bit chunks
        foreach (str_split($randomBytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        for ($i = 0; $i < 16; $i++) {
            $chunk      = substr($bits, $i * 5, 5);
            $randomStr .= self::ENCODING[bindec($chunk)];
        }

        $ulid = $timeStr . $randomStr;

        return $lowercase ? strtolower($ulid) : $ulid;
    }
}
