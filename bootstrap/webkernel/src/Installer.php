<?php declare(strict_types=1);

namespace Webkernel;

use Illuminate\Support\Facades\File;
use Webkernel\Async\Pool;
use Webkernel\Async\Promise;
use Webkernel\Http\Git\AdapterResolver;
use Webkernel\Http\Git\Contracts\GitHostAdapter;
use Webkernel\Http\Git\Exceptions\NetworkException;
use Webkernel\Registry\Providers;
use Webkernel\Registry\Source;
use Webkernel\Registry\Token;

/**
 * Webkernel module installer and updater.
 *
 * Single entry point for installing or updating any module from any supported
 * registry. The caller never imports an adapter directly — provider resolution
 * is internal.
 *
 * Expressive chain examples:
 *
 *   // Install a public third-party module from GitHub:
 *   Installer::module(
 *       from: Providers::GitHub,
 *       vendor: 'emyassine',
 *       slug: 'webkernel-module',
 *   )->toVersion('1.0.0')->async()->await();
 *
 *   // Install a private second-party module with a token:
 *   Installer::module(
 *       from: 'git.numerimondes.com',
 *       vendor: 'acme',
 *       slug: 'erp',
 *       party: 'second',
 *   )->withToken('glpat-xxx')->async()->await();
 *
 *   // Install from a canonical ID string:
 *   Installer::fromId('github-com::emyassine/webkernel-module')
 *       ->toVersion('1.0.0')
 *       ->async()
 *       ->then(fn($path) => log("Installed at {$path}"))
 *       ->await();
 *
 *   // Install multiple modules in parallel:
 *   Pool::allOrFail([
 *       'mod-a' => Installer::fromId('github-com::vendor/mod-a')->async(),
 *       'mod-b' => Installer::fromId('github-com::vendor/mod-b')->async(),
 *   ]);
 */
final class Installer
{
    private const HOOK_INSTALL_FILE = 'webkernel-install.php';
    private const HOOK_UPDATE_FILE  = 'webkernel-update.php';

    private ?string $targetVersion       = null;
    private ?string $explicitToken       = null;
    private bool    $createBackup        = true;
    private bool    $includePreReleases  = false;
    private bool    $runHooks            = true;
    private ?string $installPathOverride = null;

    private AdapterResolver $resolver;
    private Token           $tokenStore;

    private function __construct(private readonly Source $source)
    {
        $this->tokenStore = new Token();
        $this->resolver   = new AdapterResolver($this->tokenStore);
    }

    // ── Static entry points ───────────────────────────────────────────────────

    /**
     * Install or update a module identified by registry, vendor, and slug.
     *
     *   Installer::module(Providers::GitHub, 'emyassine', 'webkernel-module')
     */
    public static function module(
        Providers|string $from,
        string           $vendor,
        string           $slug,
        string           $party      = 'third',
        ?string          $version    = null,
        bool             $private    = false,
        ?string          $customBase = null,
    ): self {
        $source = Source::from(
            provider:   $from,
            vendor:     $vendor,
            slug:       $slug,
            party:      $party,
            version:    $version,
            private:    $private,
            customBase: $customBase,
        );

        return new self($source);
    }

    /**
     * Install or update a module from its canonical ID string.
     *
     *   Installer::fromId('github-com::emyassine/webkernel-module')
     */
    public static function fromId(string $id, string $party = 'third', ?string $version = null): self
    {
        return new self(Source::fromId($id, $party, $version));
    }

    // ── Configuration ─────────────────────────────────────────────────────────

    public function toVersion(string $version): self
    {
        $this->targetVersion = $version;
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

    public function withHooks(bool $run = true): self
    {
        $this->runHooks = $run;
        return $this;
    }

    public function includePreReleases(bool $include = true): self
    {
        $this->includePreReleases = $include;
        return $this;
    }

    /**
     * Override the install path (by default derived from the module manifest).
     */
    public function installTo(string $path): self
    {
        $this->installPathOverride = $path;
        return $this;
    }

    // ── Execution ─────────────────────────────────────────────────────────────

    /**
     * Return a Promise for the full install / update flow.
     * The resolved value is the path where the module was installed.
     *
     *   $installer->async()->then(fn($path) => ...)->await();
     */
    public function async(): Promise
    {
        return Promise::resolve(fn () => $this->execute());
    }

    /**
     * Run synchronously and return the install path.
     *
     * @throws NetworkException
     * @throws \RuntimeException
     */
    public function execute(): string
    {
        $adapter = $this->buildAdapter();
        $release = $this->pickRelease($adapter);
        $tempDir = $this->tempDir('wk-install-');

        try {
            $releaseWithSource         = $release;
            $releaseWithSource['_source'] = $this->source;
            $adapter->download($releaseWithSource, $tempDir);

            if ($this->runHooks) {
                $this->runHook($tempDir, self::HOOK_INSTALL_FILE);
            }

            $installPath = $this->resolveInstallPath($tempDir);
            $this->install($tempDir, $installPath);

            return $installPath;
        } catch (\Throwable $e) {
            if (is_dir($tempDir)) {
                File::deleteDirectory($tempDir);
            }
            throw $e;
        }
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    private function buildAdapter(): GitHostAdapter
    {
        $adapter = $this->resolver->resolve($this->source);

        if ($this->explicitToken !== null) {
            return $adapter->withToken($this->explicitToken);
        }

        return $adapter;
    }

    /**
     * @return array<string, mixed>
     */
    private function pickRelease(\Webkernel\Http\Git\Contracts\GitHostAdapter $adapter): array
    {
        $releases = $adapter->releases($this->source, $this->includePreReleases);

        if (empty($releases)) {
            throw new NetworkException("No releases found for [{$this->source}].");
        }

        $version = $this->targetVersion ?? $this->source->version;

        if ($version === null) {
            return $releases[0]; // latest
        }

        foreach ($releases as $release) {
            if (($release['tag_name'] ?? '') === $version) {
                return $release;
            }
        }

        throw new NetworkException("Version [{$version}] not found for [{$this->source}].");
    }

    private function runHook(string $dir, string $filename): void
    {
        $hook = $dir . '/' . $filename;

        if (is_file($hook)) {
            (static function (string $file): void {
                require $file;
            })($hook);
        }
    }

    private function resolveInstallPath(string $tempDir): string
    {
        if ($this->installPathOverride !== null) {
            return $this->installPathOverride;
        }

        // Read the module manifest to find the declared install location
        $manifest = $tempDir . '/module.php';

        if (is_file($manifest)) {
            $data        = require $manifest;
            $installPath = $data['install_path'] ?? null;

            if (is_string($installPath) && $installPath !== '') {
                return base_path($installPath);
            }
        }

        // Fall back to the conventional modules path
        $modulesRoot = defined('WEBKERNEL_MODULES_ROOT')
            ? WEBKERNEL_MODULES_ROOT
            : base_path('modules');

        return $modulesRoot
            . '/' . $this->source->registry
            . '/' . $this->source->vendor
            . '/' . $this->source->slug;
    }

    private function install(string $tempDir, string $targetDir): void
    {
        if (is_dir($targetDir)) {
            if ($this->createBackup) {
                $backup = $targetDir . '.backup-' . date('Ymd-His');
                rename($targetDir, $backup);
            } else {
                File::deleteDirectory($targetDir);
            }
        }

        File::ensureDirectoryExists(dirname($targetDir));
        rename($tempDir, $targetDir);
    }

    private function tempDir(string $prefix): string
    {
        return sys_get_temp_dir() . '/' . $prefix . uniqid();
    }
}
