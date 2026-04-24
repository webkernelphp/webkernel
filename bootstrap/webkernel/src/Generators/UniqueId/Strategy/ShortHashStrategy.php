<?php

declare(strict_types=1);

namespace Webkernel\Generators\UniqueId\Strategy;

/**
 * ShortHash — deterministic short identifier from an input string.
 *
 * Hashes an input value (e.g. a URL, email, model key) and returns
 * a base-36 encoded slice of the hash. Useful for reproducible IDs.
 *
 * Options:
 *   input   string  The string to hash (required; falls back to random)
 *   algo    string  PHP hash algorithm (default: 'xxh3' or 'sha256')
 *   prefix  string  Custom prefix (default: '')
 *   cssSafe bool    Ensure first char is a letter (default: true)
 *
 * @example
 *   $id = (new ShortHashStrategy)->generate(10, ['input' => 'hello@world.com']);
 *   // → "k2xna73mpa"  (always the same for the same input)
 */
final class ShortHashStrategy extends AbstractStrategy
{
    public static function name(): string
    {
        return 'shorthash';
    }

    public static function description(): string
    {
        return 'Deterministic short identifier from any string input. Useful for reproducible IDs.';
    }

    public function generate(int $length = 10, array $options = []): string
    {
        $input   = isset($options['input']) ? (string) $options['input'] : bin2hex(random_bytes(8));
        $prefix  = (string) ($options['prefix']  ?? '');
        $cssSafe = (bool)   ($options['cssSafe'] ?? true);

        $algo = in_array('xxh3', hash_algos(), true) ? 'xxh3' : 'sha256';
        if (!empty($options['algo']) && in_array($options['algo'], hash_algos(), true)) {
            $algo = $options['algo'];
        }

        $hex = hash($algo, $input);

        // Convert hex → base-36 encoded integer slices
        $effectiveLength = max(1, $length - strlen($prefix));
        $output          = '';

        // Walk through hex in 8-char (32-bit) chunks and base36 encode each
        $pos = 0;
        while (strlen($output) < $effectiveLength && $pos < strlen($hex)) {
            $chunk   = substr($hex, $pos, 8);
            $int     = hexdec($chunk);
            $encoded = '';
            while ($int > 0) {
                $encoded = self::CHARS[$int % 36] . $encoded;
                $int     = intdiv($int, 36);
            }
            $output .= $encoded ?: '0';
            $pos    += 8;
        }

        $output = substr(str_pad($output, $effectiveLength, '0', STR_PAD_LEFT), 0, $effectiveLength);

        if ($cssSafe && $prefix === '' && isset($output[0]) && ctype_digit($output[0])) {
            $output[0] = self::LETTERS[(int) $output[0] % self::LETTERS_LEN];
        }

        return $prefix . $output;
    }
}
