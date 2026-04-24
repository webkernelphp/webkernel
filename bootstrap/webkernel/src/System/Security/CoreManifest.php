<?php

declare(strict_types=1);

namespace Webkernel\System\Security;
use Webkernel\Query\Traits\CacheLock;
use Webkernel\Query\Traits\Exportable;
use Webkernel\Query\Traits\FileSystemHelpers;
use Webkernel\Query\Traits\LoggerTrait;

/**
 * Webkernel — Core Manifest Manager
 *
 * Generates and verifies a SHA-256 hash manifest of core files.
 * Auto-regenerates whenever the file tree changes.
 * Safe under Octane (no request-local state retained).
 */
final class CoreManifest
{
    use CacheLock;
    use Exportable;
    use FileSystemHelpers;
    use LoggerTrait;

    // Default include/exclude paths (relative to BASE_PATH).
    private const DEFAULT_INCLUDE_REL = [
        'bootstrap',
    ];

    private const DEFAULT_EXCLUDE_REL = [
        '.git',
        'bootstrap/.git',
        'bootstrap/cache',
        'bootstrap/webkernel/runtime',
        'modules',
        'storage/webkernel/cache',
    ];

    private static ?self $instance = null;

    private function __construct() {}

    private static function instance(): self
    {
        return self::$instance ??= new self();
    }

    // ── Static API ─────────────────────────────────────────────────────────

    public static function generate(
        string $basePath,
        string $outputPath,
        array $includePaths = [],
        array $excludePaths = [],
    ): int {
        return self::instance()->generateInternal(
            $basePath,
            $outputPath,
            $includePaths,
            $excludePaths,
        );
    }

    public static function verify(
        string $basePath,
        string $manifestPath,
        array $includePaths = [],
        array $excludePaths = [],
    ): array {
        return self::instance()->verifyInternal(
            $basePath,
            $manifestPath,
            $includePaths,
            $excludePaths,
        );
    }

    // ── Instance API ──────────────────────────────────────────────────────

    private function generateInternal(
        string $basePath,
        string $outputPath,
        array $includePaths,
        array $excludePaths,
    ): int {
        [$includePaths, $excludePaths] = $this->normalizePaths(
            $basePath,
            $includePaths,
            $excludePaths,
            $outputPath,
        );

        $files       = $this->collectFiles($includePaths, $excludePaths, $basePath);
        $fingerprint = $this->fingerprint($files);
        $manifest    = [];
        $classes     = [];

        foreach ($files as $relative => $absolute) {
            $hash = hash_file('sha256', $absolute);
            if ($hash === false) {
                throw new \RuntimeException('CoreManifest: could not hash file: ' . $absolute);
            }
            $manifest[$relative] = $hash;

            if (str_ends_with($absolute, '.php')) {
                foreach ($this->extractClasses($absolute) as $class) {
                    $classes[$class][] = $relative;
                }
            }
        }

        ksort($manifest);
        ksort($classes);

        $this->ensureDirectory(dirname($outputPath));

        $generated      = date('Y-m-d H:i:s');
        $count          = count($manifest);
        $export         = $this->phpExport($manifest);
        $classMap       = $this->phpExport($classes);
        $includes       = $this->phpExport($includePaths);
        $excludes       = $this->phpExport($excludePaths);
        $basePathExport = var_export($basePath, true);

        $content = <<<PHP
        <?php

        declare(strict_types=1);

        // Webkernel core manifest — generated {$generated} — {$count} files
        // DO NOT EDIT MANUALLY.

        return [
            'generated_at' => '{$generated}',
            'count'        => {$count},
            'fingerprint'  => '{$fingerprint}',
            'base_path'    => {$basePathExport},
            'includes'     => {$includes},
            'excludes'     => {$excludes},
            'files'        => {$export},
            'classes'      => {$classMap},
        ];
        PHP;

        if (!$this->writeFile($outputPath, $content, true)) {
            throw new \RuntimeException('CoreManifest: could not write manifest to: ' . $outputPath);
        }

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($outputPath, true);
        }

        return $count;
    }

    private function verifyInternal(
        string $basePath,
        string $manifestPath,
        array $includePaths,
        array $excludePaths,
    ): array {
        [$includePaths, $excludePaths] = $this->normalizePaths(
            $basePath,
            $includePaths,
            $excludePaths,
            $manifestPath,
        );

        $files       = $this->collectFiles($includePaths, $excludePaths, $basePath);
        $fingerprint = $this->fingerprint($files);

        $payload = is_file($manifestPath) ? require $manifestPath : null;

        if (!is_array($payload) || !isset($payload['files'], $payload['fingerprint'])) {
            $this->regenerate(
                $basePath,
                $manifestPath,
                $includePaths,
                $excludePaths,
                'missing-or-invalid',
                $fingerprint,
            );
            return ['fingerprint' => $fingerprint, 'regenerated' => true];
        }

        if (!is_string($payload['fingerprint']) || !hash_equals($payload['fingerprint'], $fingerprint)) {
            $this->regenerate(
                $basePath,
                $manifestPath,
                $includePaths,
                $excludePaths,
                'stale',
                $fingerprint,
            );
            return ['fingerprint' => $fingerprint, 'regenerated' => true];
        }

        // Fast path: if fingerprint matches, skip per-file hashing.
        // Full hashes are computed during regenerate().
        return ['fingerprint' => $fingerprint, 'regenerated' => false];
    }

    private function regenerate(
        string $basePath,
        string $manifestPath,
        array $includePaths,
        array $excludePaths,
        string $reason,
        string $fingerprint,
    ): void {
        $lockPath = dirname($manifestPath) . '/.core.manifest.lock';

        $this->withLock($lockPath, function () use (
            $basePath,
            $manifestPath,
            $includePaths,
            $excludePaths,
            $reason,
            $fingerprint,
        ): void {
            $payload = is_file($manifestPath) ? require $manifestPath : null;
            if (
                is_array($payload)
                && isset($payload['fingerprint'])
                && is_string($payload['fingerprint'])
                && hash_equals($payload['fingerprint'], $fingerprint)
            ) {
                return;
            }

            $this->emitNotice("Core manifest regenerate ({$reason}) — {$manifestPath}");
            $this->generateInternal($basePath, $manifestPath, $includePaths, $excludePaths);
        });
    }

    private function normalizePaths(
        string $basePath,
        array $includePaths,
        array $excludePaths,
        ?string $manifestPath = null,
    ): array {
        $basePath = rtrim($basePath, '/\\');

        if (empty($includePaths)) {
            $includePaths = array_map(
                static fn(string $rel): string => $basePath . '/' . $rel,
                self::DEFAULT_INCLUDE_REL,
            );
        }

        if (empty($excludePaths)) {
            $excludePaths = array_map(
                static fn(string $rel): string => $basePath . '/' . $rel,
                self::DEFAULT_EXCLUDE_REL,
            );
        }

        if (is_string($manifestPath)) {
            $excludePaths[] = dirname($manifestPath);
        }

        $normalize = static function (string $path) use ($basePath): string {
            $path = str_starts_with($path, '/') ? $path : ($basePath . '/' . $path);
            return rtrim($path, '/\\');
        };

        $includePaths = array_values(array_unique(array_map($normalize, $includePaths)));
        $excludePaths = array_values(array_unique(array_map($normalize, $excludePaths)));

        return [$includePaths, $excludePaths];
    }

    private function fingerprint(array $files): string
    {
        $fingerprintParts = [];
        foreach ($files as $relative => $absolute) {
            $fingerprintParts[] = $relative
                . '|' . (@filemtime($absolute) ?: 0)
                . '|' . (@filesize($absolute) ?: 0);
        }
        return hash('sha256', implode("\n", $fingerprintParts));
    }

    /**
     * @return list<class-string>
     */
    private function extractClasses(string $file): array
    {
        $code = file_get_contents($file);
        if ($code === false) {
            return [];
        }

        $tokens    = token_get_all($code);
        $namespace = '';
        $classes   = [];
        $captureNs = false;
        $nsParts   = [];

        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];
            if (!is_array($token)) {
                if ($captureNs) {
                    $namespace = trim(implode('', $nsParts));
                    $captureNs = false;
                }
                continue;
            }

            if ($token[0] === T_NAMESPACE) {
                $captureNs = true;
                $nsParts   = [];
                continue;
            }

            if ($captureNs) {
                if ($token[0] === T_STRING || $token[0] === T_NAME_QUALIFIED) {
                    $nsParts[] = $token[1];
                } elseif ($token[0] === T_NS_SEPARATOR) {
                    $nsParts[] = '\\';
                } elseif ($token[0] === T_WHITESPACE) {
                    continue;
                } else {
                    $namespace = trim(implode('', $nsParts));
                    $captureNs = false;
                }
                continue;
            }

            if (in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) {
                $prev = $tokens[$i - 1] ?? null;
                if (is_array($prev) && in_array($prev[0], [T_NEW, T_DOUBLE_COLON], true)) {
                    continue;
                }
                for ($j = $i + 1; $j < $count; $j++) {
                    $next = $tokens[$j];
                    if (is_array($next) && $next[0] === T_STRING) {
                        $fqcn = $namespace !== '' ? $namespace . '\\' . $next[1] : $next[1];
                        $classes[] = $fqcn;
                        break;
                    }
                }
            }
        }

        return $classes;
    }
}
