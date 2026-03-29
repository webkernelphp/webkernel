<?php declare(strict_types=1);

namespace Webkernel\Http\Git;

use Webkernel\Http\Git\Exceptions\IntegrityException;

/**
 * SHA-256 checksum verification for downloaded archives.
 *
 * Each release record may carry a checksum under different field names
 * depending on the registry (GitHub uses none by default, the official
 * Webkernel registry uses "sha256", custom registries may use "checksum").
 * This class normalises the lookup.
 */
final class Checksum
{
    /**
     * Verify the SHA-256 digest of $content against $release metadata.
     *
     * When the release record carries no checksum, the method returns true
     * (no-op) — the adapter is responsible for deciding whether a missing
     * checksum is acceptable.
     *
     * @param array<string, mixed> $release
     * @throws IntegrityException  when a checksum is present but does not match
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

    /**
     * Compute the SHA-256 digest of a file on disk.
     *
     * @throws \RuntimeException  when the file cannot be read
     */
    public static function ofFile(string $path): string
    {
        $hash = hash_file('sha256', $path);

        if ($hash === false) {
            throw new \RuntimeException("Cannot compute SHA-256 for [{$path}].");
        }

        return $hash;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $release
     */
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
