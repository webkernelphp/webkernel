<?php

declare(strict_types=1);

namespace Webkernel\Generators\UniqueId\Strategy;

/**
 * RFC 9562 UUID version 7 — time-ordered, sortable.
 *
 * Embeds a millisecond Unix timestamp in the high bits, making UUIDs
 * monotonically increasing and database-index friendly.
 *
 * Format: tttttttt-tttt-7xxx-yxxx-xxxxxxxxxxxx  (t = timestamp)
 * Length parameter is ignored (always 36 chars, or 32 compact).
 *
 * Options:
 *   compact  bool  Return without dashes (default: false)
 *
 * @example
 *   $id = (new UuidV7Strategy)->generate();
 *   // → "018f7e6a-d415-7000-93b4-9a7b3c5d6e1f"
 */
final class UuidV7Strategy extends AbstractStrategy
{
    public static function name(): string
    {
        return 'uuidv7';
    }

    public static function description(): string
    {
        return 'RFC 9562 UUID v7 — millisecond-timestamp prefix, time-sortable, DB-index friendly.';
    }

    public function generate(int $length = 36, array $options = []): string
    {
        $compact = (bool) ($options['compact'] ?? false);

        // 48-bit millisecond timestamp
        $msec = (int) (microtime(true) * 1000);

        $bytes = random_bytes(16);

        // Bytes 0–5: Unix timestamp in ms (big-endian)
        $bytes[0] = chr(($msec >> 40) & 0xff);
        $bytes[1] = chr(($msec >> 32) & 0xff);
        $bytes[2] = chr(($msec >> 24) & 0xff);
        $bytes[3] = chr(($msec >> 16) & 0xff);
        $bytes[4] = chr(($msec >> 8)  & 0xff);
        $bytes[5] = chr($msec         & 0xff);

        // Version 7
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x70);
        // Variant 10xx
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        $hex = bin2hex($bytes);

        if ($compact) {
            return $hex;
        }

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }
}
