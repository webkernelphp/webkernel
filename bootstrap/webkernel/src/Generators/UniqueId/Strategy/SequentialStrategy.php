<?php

declare(strict_types=1);

namespace Webkernel\Generators\UniqueId\Strategy;

/**
 * Counter-based sequential identifier with a random per-session prefix.
 *
 * IDs are deterministic within a session but unpredictable across sessions.
 * The static counter advances atomically. Not safe for multi-process/worker
 * environments without injecting an external counter store.
 *
 * Options:
 *   prefix   string  Custom prefix (default: '')
 *   cssSafe  bool    Ensure first char is a letter (default: true)
 *
 * @example
 *   $id = (new SequentialStrategy)->generate(12);
 *   // → "a4f8c200000a"  (session-prefix + base36 counter, padded)
 */
final class SequentialStrategy extends AbstractStrategy
{
    private static int $counter = 0;
    private static ?string $sessionPrefix = null;

    public static function name(): string
    {
        return 'sequential';
    }

    public static function description(): string
    {
        return 'Counter-based ID with a random per-session prefix — deterministic ordering, unpredictable prefix.';
    }

    public function generate(int $length = 12, array $options = []): string
    {
        $prefix  = (string) ($options['prefix']  ?? '');
        $cssSafe = (bool)   ($options['cssSafe'] ?? true);

        if (self::$sessionPrefix === null) {
            self::$sessionPrefix = substr(bin2hex(random_bytes(4)), 0, 6);
        }

        $effectiveLength = max(1, $length - strlen($prefix));
        $num             = self::$counter++;

        // Base-36 encode the counter
        $encoded = '';
        $tmp     = $num;
        do {
            $encoded = self::CHARS[$tmp % 36] . $encoded;
            $tmp     = intdiv($tmp, 36);
        } while ($tmp > 0);

        $raw = $prefix . self::$sessionPrefix . $encoded;
        $raw = str_pad($raw, strlen($prefix) + $effectiveLength, '0', STR_PAD_RIGHT);
        $id  = substr($raw, 0, strlen($prefix) + $effectiveLength);

        if ($cssSafe && $prefix === '' && isset($id[0]) && ctype_digit($id[0])) {
            $id[0] = self::LETTERS[(int) $id[0] % self::LETTERS_LEN];
        }

        return $id;
    }

    /**
     * Reset the per-session counter and prefix (useful in tests).
     */
    public static function reset(): void
    {
        self::$counter       = 0;
        self::$sessionPrefix = null;
    }
}
