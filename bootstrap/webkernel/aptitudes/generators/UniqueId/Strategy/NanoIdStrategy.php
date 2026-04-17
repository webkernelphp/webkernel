<?php

declare(strict_types=1);

namespace Webkernel\Generators\UniqueId\Strategy;

/**
 * NanoID-compatible identifier — URL-safe, configurable alphabet, no deps.
 *
 * Uses the same rejection-sampling algorithm as the official NanoID spec
 * to ensure uniform distribution across the alphabet.
 *
 * Options:
 *   alphabet  string  Custom alphabet (default: URL-safe 64-char set)
 *   cssSafe   bool    Force first char to be a letter (default: true)
 *
 * @example
 *   $id = (new NanoIdStrategy)->generate(21);
 *   // → "V1StGXR8_Z5jdHi6B-myT"
 *
 *   $id = (new NanoIdStrategy)->generate(10, ['alphabet' => '0123456789abcdef']);
 *   // → "3a5f9e2b1c"
 */
final class NanoIdStrategy extends AbstractStrategy
{
    private const URL_SAFE_ALPHABET = 'useandom-26T198340PX75pxJACKVERYMINDBUSHWOLF_GQZbfghjklqvwyzrict';

    public static function name(): string
    {
        return 'nanoid';
    }

    public static function description(): string
    {
        return 'NanoID-compatible: URL-safe, configurable alphabet, uniform distribution. Pure PHP.';
    }

    public function generate(int $length = 21, array $options = []): string
    {
        $alphabet = (string) ($options['alphabet'] ?? self::URL_SAFE_ALPHABET);
        $cssSafe  = (bool)   ($options['cssSafe']  ?? true);

        $alphabetSize = strlen($alphabet);
        if ($alphabetSize < 2) {
            throw new \InvalidArgumentException('NanoID alphabet must have at least 2 characters.');
        }

        // Rejection-sampling mask (from official NanoID spec)
        $mask  = (int) (pow(2, ceil(log($alphabetSize, 2))) - 1);
        $step  = (int) ceil(1.6 * $mask * $length / $alphabetSize);

        $id    = '';
        $first = true;

        while (strlen($id) < $length) {
            $bytes = random_bytes($step);
            for ($i = 0; $i < $step && strlen($id) < $length; $i++) {
                $byte = ord($bytes[$i]) & $mask;
                if ($byte < $alphabetSize) {
                    $char = $alphabet[$byte];
                    // CSS-safe: ensure first char is a letter from [a-zA-Z]
                    if ($cssSafe && $first && !ctype_alpha($char)) {
                        continue; // skip non-letter first chars
                    }
                    $id   .= $char;
                    $first = false;
                }
            }
        }

        return $id;
    }
}
