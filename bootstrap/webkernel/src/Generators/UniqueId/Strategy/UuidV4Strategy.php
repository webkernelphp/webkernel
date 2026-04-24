<?php

declare(strict_types=1);

namespace Webkernel\Generators\UniqueId\Strategy;

/**
 * RFC 4122 UUID version 4 (randomly generated).
 *
 * Pure PHP, zero dependencies. Format: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
 * Length parameter is ignored (UUIDs are always 36 chars).
 *
 * Options:
 *   compact  bool  Return without dashes (default: false) → 32 chars
 *
 * @example
 *   $id = (new UuidV4Strategy)->generate();
 *   // → "550e8400-e29b-41d4-a716-446655440000"
 *
 *   $id = (new UuidV4Strategy)->generate(36, ['compact' => true]);
 *   // → "550e8400e29b41d4a716446655440000"
 */
final class UuidV4Strategy extends AbstractStrategy
{
    public static function name(): string
    {
        return 'uuidv4';
    }

    public static function description(): string
    {
        return 'RFC 4122 UUID v4 — 122-bit random, universally unique. Pure PHP.';
    }

    public function generate(int $length = 36, array $options = []): string
    {
        $compact = (bool) ($options['compact'] ?? false);

        $bytes = random_bytes(16);

        // Set version 4
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        // Set variant bits (10xx)
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
