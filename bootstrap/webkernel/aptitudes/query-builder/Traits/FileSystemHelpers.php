<?php declare(strict_types=1);
namespace Webkernel\Query\Traits;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

trait FileSystemHelpers
{
    private function ensureDirectory(string $dir, int $mode = 0755): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, $mode, true);
        }
    }

    private function writeFile(string $path, string $content, bool $lock = true): bool
    {
        return file_put_contents($path, $content, $lock ? LOCK_EX : 0) !== false;
    }

    /**
     * @param  list<string>          $includePaths
     * @param  list<string>          $excludePaths
     * @return array<string, string>
     */
    private function collectFiles(array $includePaths, array $excludePaths = [], ?string $basePath = null): array
    {
        $files    = [];
        $basePath = is_string($basePath) ? rtrim($basePath, '/\\') : null;
        foreach ($includePaths as $path) {
            if (is_file($path) && !$this->isExcluded($path, $excludePaths)) {
                $key         = $basePath !== null ? ltrim(str_replace($basePath, '', $path), '/\\') : basename($path);
                $files[$key] = $path;
                continue;
            }
            if (!is_dir($path)) {
                continue;
            }
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) {
                $abs = $file->getRealPath();
                if ($abs && !$this->isExcluded($abs, $excludePaths)) {
                    $key         = $basePath !== null ? ltrim(str_replace($basePath, '', $abs), '/\\') : ltrim(str_replace($path, '', $abs), '/\\');
                    $files[$key] = $abs;
                }
            }
        }
        ksort($files);
        return $files;
    }

    private function isExcluded(string $path, array $excludePaths): bool
    {
        foreach ($excludePaths as $exclude) {
            if ($path === $exclude || str_starts_with($path, $exclude . DIRECTORY_SEPARATOR)) {
                return true;
            }
        }
        return false;
    }
}
