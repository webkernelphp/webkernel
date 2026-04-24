<?php

declare(strict_types=1);

namespace Webkernel\Generators\UniqueId\Strategy;

/**
 * Microsecond-timestamp-based identifier (FASTEST strategy, ~0.5μs).
 *
 * IDs are base-36 encoded timestamps. When generating many IDs in a tight
 * loop the timestamp is incremented by 1 per ID to guarantee uniqueness.
 *
 * Options:
 *   prefix   string  Custom prefix (default: '')
 *   cssSafe  bool    Ensure first char is a letter (default: true)
 *
 * @example
 *   $id = (new EpochStrategy)->generate(12);
 *   // → "e0mzk4x7a1q2"
 */
final class EpochStrategy extends AbstractStrategy
{
    public static function name(): string
    {
        return 'epoch';
    }

    public static function description(): string
    {
        return 'Microsecond-timestamp base-36 encoded identifier (~0.5μs, monotonically increasing).';
    }

    public function generate(int $length = 12, array $options = []): string
    {
        return $this->many(1, $length, $options)[0];
    }

    public function many(int $count, int $length = 12, array $options = []): array
    {
        $prefix  = (string) ($options['prefix']  ?? '');
        $cssSafe = (bool)   ($options['cssSafe'] ?? true);

        $effectiveLength = max(1, $length - strlen($prefix));
        $base            = (int) (microtime(true) * 1_000_000);
        $identifiers     = [];

        for ($i = 0; $i < $count; $i++) {
            $ts      = $base + $i;
            $encoded = $this->base36($ts, $effectiveLength);

            if ($cssSafe && $prefix === '' && isset($encoded[0]) && ctype_digit($encoded[0])) {
                $encoded[0] = self::LETTERS[(int) $encoded[0] % self::LETTERS_LEN];
            }

            $identifiers[] = $prefix . $encoded;
        }

        return $identifiers;
    }
}
