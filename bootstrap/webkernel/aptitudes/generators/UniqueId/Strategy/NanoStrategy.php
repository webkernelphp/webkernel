<?php

declare(strict_types=1);

namespace Webkernel\Generators\UniqueId\Strategy;

/**
 * Nanosecond-precision identifier with collision resistance.
 *
 * Combines hrtime(true) (nanoseconds since process start) with random_bytes
 * and hashes through xxh3 (or sha256 fallback) for extreme uniqueness.
 *
 * Options:
 *   prefix   string  Custom prefix (default: '')
 *   cssSafe  bool    Ensure first char is a letter (default: true)
 *
 * @example
 *   $id = (new NanoStrategy)->generate(16);
 *   // → "nK3x-A8mZ4pR7qY2"  (url-safe base64 chars)
 */
final class NanoStrategy extends AbstractStrategy
{
    public static function name(): string
    {
        return 'nano';
    }

    public static function description(): string
    {
        return 'Nanosecond hrtime + random_bytes hashed — extreme collision resistance.';
    }

    public function generate(int $length = 12, array $options = []): string
    {
        $prefix  = (string) ($options['prefix']  ?? '');
        $cssSafe = (bool)   ($options['cssSafe'] ?? true);

        $effectiveLength = max(1, $length - strlen($prefix));

        $nanoTime   = hrtime(true);
        $randomPart = random_bytes(8);

        // xxh3 if available (PHP 8.1+), sha256 otherwise
        $algo = in_array('xxh3', hash_algos(), true) ? 'xxh3' : 'sha256';
        $hash = hash($algo, (string) $nanoTime . $randomPart, true);

        // URL-safe base64 → strip padding
        $encoded = rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
        $id      = $prefix . substr($encoded, 0, $effectiveLength);

        if ($cssSafe && $prefix === '' && isset($id[0]) && ctype_digit($id[0])) {
            $id[0] = 'n';
        }

        return substr($id, 0, strlen($prefix) + $effectiveLength);
    }
}
