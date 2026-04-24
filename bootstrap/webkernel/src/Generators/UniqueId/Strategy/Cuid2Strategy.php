<?php

declare(strict_types=1);

namespace Webkernel\Generators\UniqueId\Strategy;

/**
 * CUID2-inspired collision-resistant identifier.
 *
 * Combines:
 *   - letter prefix (always starts with a lowercase letter)
 *   - millisecond timestamp base-36
 *   - fingerprint (hostname hash)
 *   - random bytes
 * then SHA3-256 hashed and base-36 encoded.
 *
 * Length: 8–32 chars (default 24).
 *
 * @example
 *   $id = (new Cuid2Strategy)->generate(24);
 *   // → "clh3e7xkq0000la08nzq2c4v"
 */
final class Cuid2Strategy extends AbstractStrategy
{
    private static ?string $fingerprint = null;

    public static function name(): string
    {
        return 'cuid2';
    }

    public static function description(): string
    {
        return 'CUID2-inspired: timestamp + fingerprint + random, SHA3-256 hashed. Collision-resistant.';
    }

    public function generate(int $length = 24, array $options = []): string
    {
        $length = max(8, min(32, $length));

        $timestamp   = (int) (microtime(true) * 1000);
        $fingerprint = $this->getFingerprint();
        $random      = random_bytes(16);

        $payload  = $timestamp . $fingerprint . bin2hex($random) . uniqid('', true);
        $algo     = in_array('sha3-256', hash_algos(), true) ? 'sha3-256' : 'sha256';
        $hash     = hash($algo, $payload);

        // Convert hex hash to base-36
        $bigInt  = hexdec(substr($hash, 0, 15)); // Use first 60-bit chunk (safe for float)
        $encoded = $this->base36((int) $bigInt, $length - 1);

        // Always prepend a letter
        $prefix  = self::LETTERS[hexdec($hash[0]) % self::LETTERS_LEN];

        return $prefix . substr($encoded, 0, $length - 1);
    }

    private function getFingerprint(): string
    {
        if (self::$fingerprint === null) {
            $hostname         = function_exists('gethostname') ? (string) gethostname() : 'unknown';
            self::$fingerprint = substr(md5($hostname . PHP_SAPI . PHP_VERSION), 0, 10);
        }
        return self::$fingerprint;
    }
}
