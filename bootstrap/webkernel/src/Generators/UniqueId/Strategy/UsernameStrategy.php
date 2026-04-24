<?php

declare(strict_types=1);

namespace Webkernel\Generators\UniqueId\Strategy;

/**
 * Human-readable slug derived from a name + random suffix.
 *
 * Options:
 *   name     string  Input name to slugify (default: 'user')
 *   prefix   string  Custom prefix (default: '')
 *   cssSafe  bool    CSS safety (default: true, first char always letter)
 *
 * @example
 *   $id = (new UsernameStrategy)->generate(16, ['name' => 'John Doe']);
 *   // → "johndoe_a8x4k2mn"
 */
final class UsernameStrategy extends AbstractStrategy
{
    public static function name(): string
    {
        return 'username';
    }

    public static function description(): string
    {
        return 'Slugified name + random suffix — human-readable identifiers.';
    }

    public function generate(int $length = 16, array $options = []): string
    {
        return $this->many(1, $length, $options)[0];
    }

    public function many(int $count, int $length = 16, array $options = []): array
    {
        $name    = (string) ($options['name']    ?? 'user');
        $prefix  = (string) ($options['prefix']  ?? '');

        // Transliterate accented chars if iconv is available
        $slug = $this->slugify($name);

        $maxSlugLen = max(1, (int) ($length * 0.6));
        if (strlen($slug) > $maxSlugLen) {
            $slug = substr($slug, 0, $maxSlugLen);
        }

        $suffixLen = $length - strlen($slug) - 1; // -1 for underscore separator
        if ($suffixLen < 2) {
            $suffixLen = 4;
        }

        $totalBytes  = $count * $suffixLen;
        $randomBytes = random_bytes($totalBytes);
        $byteIndex   = 0;
        $identifiers = [];

        for ($i = 0; $i < $count; $i++) {
            $suffix = '';
            for ($j = 0; $j < $suffixLen; $j++) {
                $suffix .= self::CHARS[ord($randomBytes[$byteIndex++]) % self::CHARS_LEN];
            }
            $identifiers[] = $prefix . $slug . '_' . $suffix;
        }

        return $identifiers;
    }

    private function slugify(string $name): string
    {
        // Try transliteration for international characters
        if (function_exists('iconv')) {
            $translit = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
            if ($translit !== false) {
                $name = $translit;
            }
        }

        $slug = strtolower((string) preg_replace('/[^a-z0-9]+/i', '', $name));
        return $slug !== '' ? $slug : 'user';
    }
}
