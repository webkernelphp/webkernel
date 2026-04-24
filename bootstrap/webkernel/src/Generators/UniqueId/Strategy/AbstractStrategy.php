<?php

declare(strict_types=1);

namespace Webkernel\Generators\UniqueId\Strategy;

use Webkernel\Generators\UniqueId\Contracts\IdentifierStrategyInterface;

abstract class AbstractStrategy implements IdentifierStrategyInterface
{
    /** Base-36 alphabet (lowercase, alphanumeric — CSS-safe after first char) */
    protected const CHARS = 'abcdefghijklmnopqrstuvwxyz0123456789';
    protected const LETTERS = 'abcdefghijklmnopqrstuvwxyz';
    protected const CHARS_LEN = 36;
    protected const LETTERS_LEN = 26;

    /**
     * Ensure the first character is a letter (CSS class / HTML id safety).
     */
    protected function ensureCssSafe(string $id, string $prefix): string
    {
        if ($prefix === '' && $id !== '' && ctype_digit($id[0])) {
            $id[0] = self::LETTERS[(int) $id[0] % self::LETTERS_LEN];
        }
        return $id;
    }

    /**
     * Encode an integer as base-36 string, left-padded to $length.
     */
    protected function base36(int $value, int $length): string
    {
        $encoded = '';
        while ($value > 0 && strlen($encoded) < $length) {
            $encoded = self::CHARS[$value % 36] . $encoded;
            $value = intdiv($value, 36);
        }
        return str_pad($encoded, $length, self::CHARS[0], STR_PAD_LEFT);
    }

    /**
     * {@inheritdoc}
     */
    public function many(int $count, int $length = 12, array $options = []): array
    {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $ids[] = $this->generate($length, $options);
        }
        return $ids;
    }
}
