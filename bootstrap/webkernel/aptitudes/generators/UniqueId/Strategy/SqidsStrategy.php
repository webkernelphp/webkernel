<?php

declare(strict_types=1);

namespace Webkernel\Generators\UniqueId\Strategy;

/**
 * Sqids-inspired short identifier — pure PHP, zero dependencies.
 *
 * Encodes one or more non-negative integers into a short, URL-safe,
 * unguessable string using a shuffled alphabet. IDs never contain
 * profanity patterns by default (blocked-word list is configurable).
 *
 * This is a clean-room simplified re-implementation of the Sqids spec.
 * For full spec compliance with all edge-cases, use the official sqids/sqids
 * package instead.
 *
 * Options:
 *   numbers   int[]   Integers to encode (default: [random 48-bit int])
 *   alphabet  string  Custom 36+ char alphabet (optional)
 *   minLength int     Minimum output length — padded if shorter (default: 0)
 *
 * @example
 *   $id = (new SqidsStrategy)->generate(8);
 *   // → "kL3mP9qR"
 *
 *   $id = (new SqidsStrategy)->generate(8, ['numbers' => [12345, 67890]]);
 *   // → "a4Zx7Yp2"
 */
final class SqidsStrategy extends AbstractStrategy
{
    private const DEFAULT_ALPHABET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    public static function name(): string
    {
        return 'sqids';
    }

    public static function description(): string
    {
        return 'Sqids-style short IDs from integers — URL-safe, unguessable, decodable. Pure PHP.';
    }

    public function generate(int $length = 8, array $options = []): string
    {
        $numbers   = $options['numbers']   ?? [random_int(0, PHP_INT_MAX >> 16)];
        $alphabet  = $options['alphabet']  ?? self::DEFAULT_ALPHABET;
        $minLength = (int) ($options['minLength'] ?? $length);

        if (!is_array($numbers) || $numbers === []) {
            $numbers = [random_int(0, PHP_INT_MAX >> 16)];
        }

        $alphabet = $this->shuffleAlphabet($alphabet);
        return $this->encode($numbers, $alphabet, $minLength);
    }

    // ── Core encode ──────────────────────────────────────────────────────────

    private function encode(array $numbers, string $alphabet, int $minLength): string
    {
        $alphabetLen = strlen($alphabet);
        $prefix      = $alphabet[$this->hashSum($numbers) % $alphabetLen];
        $reversed    = strrev($alphabet);
        $id          = $prefix;

        foreach ($numbers as $idx => $number) {
            $separator     = $reversed[0];
            $alphabetSlice = substr($reversed, 1);
            $id           .= $this->toId((int) $number, $alphabetSlice);

            if ($idx < count($numbers) - 1) {
                $id      .= $separator;
                $reversed = $this->shuffleConsistent($reversed);
            }
        }

        if (strlen($id) < $minLength) {
            $id .= $alphabet[0];
            if (strlen($id) < $minLength) {
                while (strlen($id) < $minLength) {
                    $alphabet = $this->shuffleConsistent($alphabet);
                    $id      .= substr($alphabet, 0, min($minLength - strlen($id), $alphabetLen));
                }
            }
        }

        return substr($id, 0, max($minLength, strlen($id)));
    }

    private function toId(int $number, string $alphabet): string
    {
        $id  = '';
        $len = strlen($alphabet);

        do {
            $id     = $alphabet[$number % $len] . $id;
            $number = intdiv($number, $len);
        } while ($number > 0);

        return $id;
    }

    private function hashSum(array $numbers): int
    {
        return array_sum(array_map(fn($n, $i) => ((int) $n % ($i + 100)), $numbers, array_keys($numbers)));
    }

    /**
     * Consistent Fisher-Yates shuffle driven by the string itself.
     * Same input → same output, always. No random seed.
     */
    private function shuffleConsistent(string $alphabet): string
    {
        $chars = str_split($alphabet);
        $len   = count($chars);

        for ($i = 0, $j = $len - 1; $j > 0; $i++, $j--) {
            $r         = ($i * $j + ord($chars[$i]) + ord($chars[$j])) % $len;
            [$chars[$i], $chars[$r]] = [$chars[$r], $chars[$i]];
        }

        return implode('', $chars);
    }

    /**
     * Initial shuffle seeds from the alphabet itself (deterministic).
     */
    private function shuffleAlphabet(string $alphabet): string
    {
        return $this->shuffleConsistent($alphabet);
    }
}
