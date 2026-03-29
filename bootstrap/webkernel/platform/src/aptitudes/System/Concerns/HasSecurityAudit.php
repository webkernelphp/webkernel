<?php declare(strict_types=1);

namespace Webkernel\Aptitudes\System\Concerns;

/**
 * Dangerous-function scanner for the Security tab of the Maintenance page.
 *
 * Scans PHP source files for patterns matching WEBKERNEL_DANGEROUS_FUNCTIONS.
 * Results are sorted by descending risk score.
 *
 * No shell_exec. Pure PHP file iteration with regex matching.
 */
trait HasSecurityAudit
{
    /**
     * Scan a directory tree for dangerous PHP function calls.
     *
     * @param  string              $baseDir  Absolute path to scan.
     * @return array<int, array{  function: string, file: string, score: int, category: string, description: string, color: string }>
     */
    protected function scanDangerousFunctions(string $baseDir): array
    {
        if (!is_dir($baseDir)) {
            return [['error' => 'Directory not found: ' . $baseDir]];
        }

        $registry = WEBKERNEL_DANGEROUS_FUNCTIONS;
        $findings = [];

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($baseDir, \FilesystemIterator::SKIP_DOTS)
            );

            /** @var \SplFileInfo $file */
            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $source   = @file_get_contents($file->getPathname());
                $relative = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $file->getPathname());

                if ($source === false) {
                    continue;
                }

                foreach ($registry as $funcName => $meta) {
                    $pattern = '/\b' . preg_quote($funcName, '/') . '\s*\(/';

                    if (preg_match($pattern, $source)) {
                        $score      = (int) $meta['score'];
                        $findings[] = [
                            'function'    => $funcName,
                            'file'        => $relative,
                            'score'       => $score,
                            'category'    => (string) $meta['category'],
                            'description' => (string) $meta['description'],
                            'color'       => $score >= WEBKERNEL_SECURITY_SCORE_CRITICAL ? 'danger'
                                : ($score >= 60 ? 'warning' : 'gray'),
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            return [['error' => $e->getMessage()]];
        }

        usort($findings, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);

        return $findings;
    }
}
