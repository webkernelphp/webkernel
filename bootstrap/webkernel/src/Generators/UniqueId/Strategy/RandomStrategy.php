<?php

declare(strict_types=1);

namespace Webkernel\Generators\UniqueId\Strategy;

/**
 * Cryptographically random identifier.
 *
 * Options:
 *   prefix   string  Custom prefix (default: '')
 *   cssSafe  bool    Ensure first char is a letter (default: true)
 *
 * @example
 *   $id = (new RandomStrategy)->generate(12);
 *   // → "kx7mq2a4nrpb"
 */
final class RandomStrategy extends AbstractStrategy
{
    public static function name(): string
    {
        return 'random';
    }

    public static function description(): string
    {
        return 'Cryptographically secure random alphanumeric identifier (batch-optimised).';
    }

    public function generate(int $length = 12, array $options = []): string
    {
        return $this->many(1, $length, $options)[0];
    }

    /**
     * Batch-generates all identifiers with a single random_bytes() call.
     *
     * @return string[]
     */
    public function many(int $count, int $length = 12, array $options = []): array
    {
        $prefix  = (string) ($options['prefix']  ?? '');
        $cssSafe = (bool)   ($options['cssSafe'] ?? true);

        $effectiveLength = max(1, $length - strlen($prefix));
        $totalBytes      = $count * $effectiveLength;

        /** @var string $randomBytes */
        $randomBytes = random_bytes($totalBytes);
        $byteIndex   = 0;
        $identifiers = [];

        for ($i = 0; $i < $count; $i++) {
            $id         = $prefix;
            $firstChar  = true;

            for ($j = 0; $j < $effectiveLength; $j++) {
                $byte = ord($randomBytes[$byteIndex++]);

                if ($cssSafe && $prefix === '' && $firstChar) {
                    // First char must be a letter
                    $id       .= self::LETTERS[$byte % self::LETTERS_LEN];
                    $firstChar = false;
                } else {
                    $id .= self::CHARS[$byte % self::CHARS_LEN];
                    $firstChar = false;
                }
            }

            $identifiers[] = $id;
        }

        return $identifiers;
    }
}
