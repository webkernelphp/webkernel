<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Domain\Updates;

use Illuminate\Support\Facades\File;
use Webkernel\Async\Promise;
use Webkernel\Integration\Git\AdapterResolver;
use Webkernel\Integration\Git\Contracts\GitHostAdapter;
use Webkernel\Integration\Git\Exceptions\NetworkException;
use Webkernel\Integration\Git\Hosting\GitHubAdapter;
use Webkernel\Registry\Providers;
use Webkernel\Registry\Source;
use Webkernel\Registry\Token;

/**
 * Downloads and safely swaps the webkernel bootstrap directory.
 *
 *   WebkernelUpdater::webkernel()
 *       ->toVersion('1.9.3+1')
 *       ->keepDirs(['var-elements'])
 *       ->withToken('ghp_...')
 *       ->execute();
 *
 * Works for any zip-distributed target (core or module) — the same
 * atomic backup → download → verify → swap → clean pipeline applies.
 */
final class WebkernelUpdater
{
    private const BOOTSTRAP_OWNER = 'webkernelphp';
    private const BOOTSTRAP_SLUG  = 'foundation';
    private const HOOK_FILE       = 'webkernel-update.php';

    private array   $preservedDirs      = ['var-elements'];
    private ?string $targetVersion      = null;
    private ?string $explicitToken      = null;
    private bool    $includePreReleases = false;
    private bool    $createBackup       = true;

    private AdapterResolver $resolver;
    private Token           $tokenStore;

    private function __construct()
    {
        $this->tokenStore = new Token();
        $this->resolver   = new AdapterResolver($this->tokenStore);
    }

    // ── Factories ─────────────────────────────────────────────────────────────

    public static function webkernel(): self
    {
        return new self();
    }

    // ── Fluent options ────────────────────────────────────────────────────────

    public function toVersion(string $version): self
    {
        $this->targetVersion = $version;
        return $this;
    }

    public function keepDirs(array $dirs): self
    {
        $this->preservedDirs = $dirs;
        return $this;
    }

    public function withToken(string $token): self
    {
        $this->explicitToken = $token;
        return $this;
    }

    public function withBackup(bool $create = true): self
    {
        $this->createBackup = $create;
        return $this;
    }

    public function includePreReleases(bool $include = true): self
    {
        $this->includePreReleases = $include;
        return $this;
    }

    public function async(): Promise
    {
        return Promise::resolve(fn () => $this->execute());
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    /**
     * @throws NetworkException
     * @throws \RuntimeException
     */
    public function execute(): string
    {
        $source  = $this->buildSource();
        $adapter = $this->buildAdapter($source);
        $release = $this->pickRelease($adapter, $source);
        $tempDir = $this->tempDir('webkernel-update-');

        $this->preserveAndDownload($adapter, $release, $tempDir, $source);
        $this->runHook($tempDir);

        return $this->swap($tempDir);
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    private function buildSource(): Source
    {
        return Source::from(
            provider: Providers::GitHub,
            vendor:   self::BOOTSTRAP_OWNER,
            slug:     self::BOOTSTRAP_SLUG,
            party:    'first',
            version:  $this->targetVersion,
        );
    }

    /** @return array<string, mixed> */
    private function pickRelease(GitHostAdapter $adapter, Source $source): array
    {
        $releases = $adapter->releases($source, $this->includePreReleases);

        if (empty($releases)) {
            throw new NetworkException('No releases found for webkernel repository.');
        }

        if ($this->targetVersion === null) {
            return $releases[0];
        }

        foreach ($releases as $release) {
            if (($release['tag_name'] ?? '') === $this->targetVersion) {
                return $release;
            }
        }

        throw new NetworkException("Webkernel version [{$this->targetVersion}] not found.");
    }

    /** @param array<string, mixed> $release */
    private function preserveAndDownload(
        GitHostAdapter $adapter,
        array          $release,
        string         $tempDir,
        Source         $source,
    ): void {
        $bootstrapDir = $this->bootstrapDir();
        $preserveDir  = $this->tempDir('webkernel-preserve-');

        try {
            $this->backupPreserved($bootstrapDir, $preserveDir);

            $release['_source'] = $source;
            $adapter->download($release, $tempDir);

            $this->restorePreserved($preserveDir, $tempDir);
        } finally {
            if (is_dir($preserveDir)) {
                File::deleteDirectory($preserveDir);
            }
        }
    }

    private function runHook(string $dir): void
    {
        $hook = $dir . '/' . self::HOOK_FILE;

        if (is_file($hook)) {
            (static function (string $file): void {
                require $file;
            })($hook);
        }
    }

    private function swap(string $tempDir): string
    {
        $target = $this->bootstrapDir();
        $oldDir = $target . '.old';

        if (is_dir($oldDir)) {
            File::deleteDirectory($oldDir);
        }

        if (is_dir($target)) {
            if ($this->createBackup) {
                rename($target, $oldDir);
            } else {
                File::deleteDirectory($target);
            }
        }

        rename($tempDir, $target);

        if (is_dir($oldDir)) {
            File::deleteDirectory($oldDir);
        }

        return $target;
    }

    private function backupPreserved(string $base, string $dest): void
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        foreach ($this->preservedDirs as $dir) {
            $src = $base . '/' . $dir;
            if (is_dir($src)) {
                File::copyDirectory($src, $dest . '/' . $dir);
            }
        }
    }

    private function restorePreserved(string $backup, string $target): void
    {
        foreach ($this->preservedDirs as $dir) {
            $src  = $backup . '/' . $dir;
            $dest = $target . '/' . $dir;

            if (!is_dir($src)) {
                continue;
            }

            if (is_dir($dest)) {
                File::deleteDirectory($dest);
            }

            File::copyDirectory($src, $dest);
        }
    }

    private function buildAdapter(Source $source): GitHostAdapter
    {
        $adapter = $this->resolver->resolve($source);
        return $this->explicitToken !== null
            ? $adapter->withToken($this->explicitToken)
            : $adapter;
    }

    private function bootstrapDir(): string
    {
        return defined('WEBKERNEL_PATH') ? WEBKERNEL_PATH : base_path('bootstrap/webkernel');
    }

    private function tempDir(string $prefix): string
    {
        return sys_get_temp_dir() . '/' . $prefix . uniqid();
    }
}
