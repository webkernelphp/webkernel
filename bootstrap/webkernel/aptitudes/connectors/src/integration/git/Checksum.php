<?php declare(strict_types=1);

namespace Webkernel\Integration\Git;

use Webkernel\Integration\Git\Exceptions\IntegrityException;

/**
 * SHA-256 checksum verification for downloaded archives.
 *
 * Normalises checksum field names across registries:
 *   GitHub: none by default
 *   Webkernel registry: "sha256"
 *   Custom: "checksum" or "digest"
 */
final class Checksum
{
    /**
     * @param array<string, mixed> $release
     * @throws IntegrityException
     */
    public static function verify(string $content, array $release): bool
    {
        $expected = self::extractExpected($release);

        if ($expected === null) {
            return true;
        }

        $actual = hash('sha256', $content);

        if (!hash_equals($expected, $actual)) {
            throw new IntegrityException(
                "SHA-256 mismatch.\n  Expected: {$expected}\n  Actual:   {$actual}"
            );
        }

        return true;
    }

    /** @throws \RuntimeException */
    public static function ofFile(string $path): string
    {
        $hash = hash_file('sha256', $path);

        if ($hash === false) {
            throw new \RuntimeException("Cannot compute SHA-256 for [{$path}].");
        }

        return $hash;
    }

    /** @param array<string, mixed> $release */
    private static function extractExpected(array $release): ?string
    {
        foreach (['sha256', 'checksum', 'digest'] as $key) {
            if (isset($release[$key]) && is_string($release[$key]) && $release[$key] !== '') {
                return strtolower($release[$key]);
            }
        }

        return null;
    }
}
