<?php declare(strict_types=1);

namespace Webkernel\Integration\Git;

use Webkernel\Integration\Git\Exceptions\NetworkException;
use ZipArchive;

/**
 * Extracts a zip archive and flattens the single top-level directory that
 * git forges inject when generating zipballs (GitHub, GitLab, Gitea all do this).
 *
 * Before: targetDir/owner-repo-abc123/src/...
 * After:  targetDir/src/...
 */
final class Archive
{
    /** @throws NetworkException */
    public static function extractString(string $content, string $targetDir): void
    {
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'wk_archive_');

        if ($tmp === false) {
            throw new NetworkException('Failed to create a temporary file for archive extraction.');
        }

        try {
            if (file_put_contents($tmp, $content) === false) {
                throw new NetworkException('Failed to write archive to a temporary file.');
            }

            self::extractFile($tmp, $targetDir);
        } finally {
            if (is_file($tmp)) {
                @unlink($tmp);
            }
        }
    }

    /** @throws NetworkException */
    public static function extractFile(string $zipPath, string $targetDir): void
    {
        $zip    = new ZipArchive();
        $result = $zip->open($zipPath);

        if ($result !== true) {
            throw new NetworkException("Failed to open ZIP archive [{$zipPath}]. ZipArchive error: {$result}");
        }

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        if (!$zip->extractTo($targetDir)) {
            $zip->close();
            throw new NetworkException("Failed to extract ZIP archive into [{$targetDir}].");
        }

        $zip->close();
        self::flatten($targetDir);
    }

    private static function flatten(string $targetDir): void
    {
        $entries = array_values(array_filter(
            scandir($targetDir) ?: [],
            static fn ($e) => !in_array($e, ['.', '..'], true),
        ));

        if (count($entries) !== 1 || !is_dir($targetDir . '/' . $entries[0])) {
            return;
        }

        $wrapperDir = $targetDir . '/' . $entries[0];

        $items = array_filter(
            scandir($wrapperDir) ?: [],
            static fn ($e) => !in_array($e, ['.', '..'], true),
        );

        foreach ($items as $item) {
            $src  = $wrapperDir . '/' . $item;
            $dest = $targetDir  . '/' . $item;

            if (!rename($src, $dest)) {
                throw new NetworkException("Failed to move [{$src}] to [{$dest}] during archive flatten.");
            }
        }

        @rmdir($wrapperDir);
    }
}
