<?php declare(strict_types=1);
namespace Webkernel\Arcanes;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Blaze\Blaze;
use Livewire\Livewire;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Webkernel\Arcanes\Matrix\ArtifactMatrix;
use Webkernel\Arcanes\Matrix\NamingHelper;
use Webkernel\Query\Traits\CacheLock;
use Webkernel\Query\Traits\Exportable;
use Webkernel\Query\Traits\FileSystemHelpers;
use Webkernel\Query\Traits\LoggerTrait;

/**
 * Sole owner of module and aptitude discovery, caching, and booting.
 *
 * Responsibility boundary: this class knows nothing about platform integrity.
 * Core file verification is handled exclusively by Webkernel\System\Security\CoreManifest
 * (alongside SealEnforcer), and runs before any provider is registered.
 * Arcanes\Modules only runs after that gate has passed.
 *
 * Cache lifecycle: SHA-256 fingerprint of all manifest mtime+size.
 * Stale or missing cache rebuilds synchronously under a file lock.
 * Cache invalidation is automatic — no artisan command needed.
 *
 * @since Webkernel Waterfall (v1.x.x)
 */
final class Modules extends ServiceProvider
{
    use CacheLock, Exportable, FileSystemHelpers, LoggerTrait;

    public function register(): void
    {
        $this->ensureDirectory(dirname(WEBKERNEL_MODULES_CACHE));
        foreach ($this->catalog() as $entry) {
            if (!($entry['active'] ?? false)) {
                continue;
            }
            foreach ($entry['providers'] ?? [] as $provider) {
                if (class_exists($provider)) {
                    $this->app->register($provider);
                }
            }
        }
    }

    public function boot(): void
    {
        foreach ($this->catalog() as $entry) {
            if (!($entry['active'] ?? false)) {
                continue;
            }
            $root = $entry['_root'];
            $this->bootHelpers($root, $entry);
            $this->bootRoutes($root, $entry);
            $this->bootConfig($root, $entry);
            $this->bootViews($root, $entry);
            $this->bootLang($root, $entry);
            $this->bootMigrations($root, $entry);
            $this->bootLivewire($root, $entry);
            $this->bootCommands($root, $entry);
            $this->bootBlaze($root, $entry);
        }
    }

    // ── Public API ────────────────────────────────────────────────────────────

    public static function makeId(string $registry, string $vendor, string $slug): string
    {
        return NamingHelper::moduleId($registry, $vendor, $slug);
    }

    public static function makeAptitudeId(string $slug): string
    {
        return NamingHelper::aptitudeId($slug);
    }

    // ── Cache ─────────────────────────────────────────────────────────────────

    /** @return list<array<string, mixed>> */
    private function catalog(): array
    {
        $path = WEBKERNEL_MODULES_CACHE;
        if (is_file($path) && filesize($path) > 0) {
            $cache   = require $path;
            $entries = $cache['entries'] ?? null;
            $fp      = $cache['fingerprint'] ?? '';
            if (is_array($entries) && is_string($fp) && $fp !== '' && hash_equals($fp, $this->fingerprint())) {
                return $entries;
            }
            $reason = (!is_array($entries) || $fp === '') ? 'malformed' : 'fingerprint_changed';
        } else {
            $reason = 'missing';
        }
        return $this->rebuild($reason);
    }

    /** @return list<array<string, mixed>> */
    private function rebuild(string $reason): array
    {
        $start   = hrtime(true);
        $all     = array_merge($this->discoverAptitudes(), $this->discoverModules());
        $valid   = $this->validate($all);
        $ordered = $this->dependencyOrder($valid);
        $this->writeCache($ordered, $reason);
        $ms     = round((hrtime(true) - $start) / 1_000_000, 2);
        $active = count(array_filter($ordered, static fn ($e) => (bool) ($e['active'] ?? false)));
        $this->warn("Cache rebuilt [{$reason}] in {$ms}ms — {$active}/" . count($ordered) . " active.");
        return $ordered;
    }

    // ── Discovery ─────────────────────────────────────────────────────────────

    /** @return array<string, array<string, mixed>> */
    private function discoverAptitudes(): array
    {
        $root = WEBKERNEL_APTITUDES_ROOT;
        if (!is_dir($root)) {
            return [];
        }
        $found = [];
        foreach (glob($root . '/*', GLOB_ONLYDIR) ?: [] as $path) {
            $file = $path . '/aptitude.php';
            if (!is_file($file)) {
                continue;
            }
            $manifest = require $file;
            if (!is_array($manifest)) {
                continue;
            }
            $folder = basename($path);
            $slug   = NamingHelper::aptitudeSlugFromFolder($folder);
            $id     = NamingHelper::aptitudeId($slug);
            $found[$id] = ArtifactMatrix::normalize(array_merge($manifest, [
                'id'        => $id,
                'registry'  => WEBKERNEL_APTITUDE_REGISTRY,
                'vendor'    => WEBKERNEL_APTITUDE_VENDOR,
                'slug'      => $slug,
                '_root'     => $path,
                '_type'     => 'aptitude',
                '_registry' => WEBKERNEL_APTITUDE_REGISTRY,
                '_vendor'   => WEBKERNEL_APTITUDE_VENDOR,
                '_slug'     => $slug,
                'namespace' => ($manifest['namespace'] ?? '') !== ''
                    ? rtrim($manifest['namespace'], '\\')
                    : NamingHelper::aptitudeNamespace($slug),
            ]));
        }
        return $found;
    }

    /** @return array<string, array<string, mixed>> */
    private function discoverModules(): array
    {
        $root = WEBKERNEL_MODULES_ROOT;
        if (!is_dir($root)) {
            return [];
        }
        $found = [];
        foreach ($this->sortedRegistries($root) as $registryPath) {
            $registry = basename($registryPath);
            foreach (glob($registryPath . '/*', GLOB_ONLYDIR) ?: [] as $vendorPath) {
                foreach (glob($vendorPath . '/*', GLOB_ONLYDIR) ?: [] as $modulePath) {
                    $file = $modulePath . '/module.php';
                    if (!is_file($file)) {
                        continue;
                    }
                    $manifest = require $file;
                    if (!is_array($manifest)) {
                        continue;
                    }
                    $vendor = basename($vendorPath);
                    $slug   = basename($modulePath);
                    $id     = NamingHelper::moduleId($registry, $vendor, $slug);
                    $found[$id] = ArtifactMatrix::normalize(array_merge($manifest, [
                        'id'        => $id,
                        '_root'     => $modulePath,
                        '_type'     => 'module',
                        '_registry' => $registry,
                        '_vendor'   => $vendor,
                        '_slug'     => $slug,
                        'namespace' => NamingHelper::normalizeModuleNamespace($manifest['namespace'] ?? '', $vendor, $slug),
                    ]));
                }
            }
        }
        return $found;
    }

    /** @return list<string> */
    private function sortedRegistries(string $root): array
    {
        $all      = glob($root . '/*', GLOB_ONLYDIR) ?: [];
        $official = $root . '/' . WEBKERNEL_OFFICIAL_REGISTRY;
        return in_array($official, $all, true)
            ? array_merge([$official], array_filter($all, static fn ($d) => $d !== $official))
            : $all;
    }

    // ── Validation ────────────────────────────────────────────────────────────

    /**
     * @param  array<string, array<string, mixed>> $all
     * @return array<string, array<string, mixed>>
     */
    private function validate(array $all): array
    {
        $valid = [];
        foreach ($all as $id => $entry) {
            $type    = $entry['_type'] ?? 'module';
            $missing = array_diff(ArtifactMatrix::required($type), array_keys($entry));
            if (!empty($missing)) {
                $this->warn("Entry [{$id}] rejected — missing: " . implode(', ', $missing));
                continue;
            }
            if (!is_bool($entry['active'])) {
                $this->warn("Entry [{$id}] rejected — 'active' must be boolean.");
                continue;
            }
            if ($type === 'module' && !str_starts_with($entry['namespace'] ?? '', 'WebModule\\')) {
                $this->warn("Module [{$id}] rejected — namespace must start with WebModule\\.");
                continue;
            }
            $valid[$id] = $entry;
        }
        return $valid;
    }

    // ── Dependency sort (Kahn topological) ───────────────────────────────────

    /**
     * @param  array<string, array<string, mixed>> $entries
     * @return list<array<string, mixed>>
     */
    private function dependencyOrder(array $entries): array
    {
        $inDegree  = array_fill_keys(array_keys($entries), 0);
        $adjacency = array_fill_keys(array_keys($entries), []);
        foreach ($entries as $id => $entry) {
            foreach ($entry['depends'] ?? [] as $dep) {
                if (!isset($entries[$dep])) {
                    $this->warn("Entry [{$id}] depends on missing [{$dep}].");
                    continue;
                }
                $adjacency[$dep][] = $id;
                $inDegree[$id]++;
            }
        }
        $queue   = array_keys(array_filter($inDegree, static fn ($d) => $d === 0));
        $ordered = [];
        while (!empty($queue)) {
            $current   = array_shift($queue);
            $ordered[] = $current;
            foreach ($adjacency[$current] as $dep) {
                if (--$inDegree[$dep] === 0) {
                    $queue[] = $dep;
                }
            }
        }
        foreach (array_diff(array_keys($entries), $ordered) as $id) {
            $this->warn("Entry [{$id}] has a circular dependency and will not be loaded.");
        }
        $result = [];
        foreach ($ordered as $id) {
            if (isset($entries[$id])) {
                $result[] = $entries[$id];
            }
        }
        return $result;
    }

    // ── Cache write ───────────────────────────────────────────────────────────

    /** @param list<array<string, mixed>> $ordered */
    private function writeCache(array $ordered, string $reason): void
    {
        $this->ensureDirectory(dirname(WEBKERNEL_MODULES_CACHE));
        $this->withLock(WEBKERNEL_MODULES_LOCK, function () use ($ordered, $reason): void {
            $now    = date('c');
            $total  = count($ordered);
            $active = count(array_filter($ordered, static fn ($e) => (bool) ($e['active'] ?? false)));
            $payload = ArtifactMatrix::buildPayload([
                'generated_at'    => $now,
                'fingerprint'     => $this->fingerprint(),
                'rebuilt_because' => $reason,
                'total'           => $total,
                'active'          => $active,
                'psr4_map'        => $this->buildPsr4Map($ordered),
                'entries'         => $ordered,
            ]);
            $content = "<?php\n/** Webkernel catalog — {$now} | v" . ArtifactMatrix::CACHE_PROTOCOL_VERSION . " | {$reason} | {$active}/{$total} */\nreturn " . $this->phpExport($payload) . ";\n";
            $this->writeFile(WEBKERNEL_MODULES_CACHE, $content, true);
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate(WEBKERNEL_MODULES_CACHE, true);
            }
        });
    }

    /**
     * @param  list<array<string, mixed>> $ordered
     * @return array<string, string>
     */
    private function buildPsr4Map(array $ordered): array
    {
        $map = [];
        foreach ($ordered as $entry) {
            if (!($entry['active'] ?? false)) {
                continue;
            }
            $ns = rtrim($entry['namespace'] ?? '', '\\') . '\\';
            if ($ns !== '\\') {
                $map[$ns] = $entry['_root'];
            }
        }
        return $map;
    }

    private function fingerprint(): string
    {
        $paths = [];
        if (is_dir(WEBKERNEL_MODULES_ROOT)) {
            $paths = array_merge($paths, glob(WEBKERNEL_MODULES_ROOT . '/*/*/*/module.php') ?: []);
        }
        if (is_dir(WEBKERNEL_APTITUDES_ROOT)) {
            $paths = array_merge($paths, glob(WEBKERNEL_APTITUDES_ROOT . '/*/aptitude.php') ?: []);
        }
        sort($paths);
        return hash('sha256', implode("\n", array_map(
            static fn ($p) => $p . '|' . (@filemtime($p) ?: 0) . '|' . (@filesize($p) ?: 0),
            $paths,
        )));
    }

    // ── Boot steps ────────────────────────────────────────────────────────────

    private function bootHelpers(string $root, array $entry): void
    {
        foreach ($entry['helpers'] ?? [] as $rel) {
            $file = $root . '/' . ltrim((string) $rel, '/');
            if (is_file($file)) {
                require_once $file;
            }
        }
        foreach ($entry['helpers_paths'] ?? [] as $spec) {
            $rel   = is_array($spec) ? (string) ($spec['path'] ?? '') : (string) $spec;
            $depth = is_array($spec) ? (int) ($spec['depth'] ?? 0) : 0;
            $dir   = $root . '/' . ltrim($rel, '/');
            if (!is_dir($dir)) {
                continue;
            }
            foreach (glob($dir . '/*.php') ?: [] as $f) {
                require_once $f;
            }
            if ($depth >= 1) {
                foreach (glob($dir . '/*', GLOB_ONLYDIR) ?: [] as $sub) {
                    foreach (glob($sub . '/*.php') ?: [] as $f) {
                        require_once $f;
                    }
                }
            }
        }
    }

    private function bootRoutes(string $root, array $entry): void
    {
        foreach ($entry['route_groups'] ?? [] as $group => $files) {
            foreach ((array) $files as $rel) {
                $file = $root . '/' . $rel;
                if (!is_file($file)) {
                    continue;
                }
                match ($group) {
                    'api'   => Route::middleware('api')->group($file),
                    'web'   => Route::middleware('web')->group($file),
                    default => Route::group([], $file),
                };
            }
        }
        foreach ($entry['route_paths'] ?? [] as $rel) {
            $dir = $root . '/' . $rel;
            if (!is_dir($dir)) {
                continue;
            }
            foreach (glob($dir . '/*.php') ?: [] as $file) {
                Route::group([], $file);
            }
        }
    }

    private function bootConfig(string $root, array $entry): void
    {
        foreach ($entry['config_paths'] ?? [] as $rel) {
            $dir = $root . '/' . ltrim((string) $rel, '/');
            if (!is_dir($dir)) {
                continue;
            }
            foreach (glob($dir . '/*.php') ?: [] as $file) {
                $key = pathinfo($file, PATHINFO_FILENAME);
                if (!$this->app['config']->has($key)) {
                    $this->mergeConfigFrom($file, $key);
                }
            }
        }
    }

    private function bootViews(string $root, array $entry): void
    {
        foreach ($entry['view_namespaces'] ?? [] as $handle => $rel) {
            $dir = $root . '/' . ltrim((string) $rel, '/');
            if (is_dir($dir)) {
                $this->loadViewsFrom($dir, (string) $handle);
            }
        }
    }

    private function bootLang(string $root, array $entry): void
    {
        foreach ($entry['lang_paths'] ?? [] as $handle => $rel) {
            $dir = $root . '/' . ltrim((string) $rel, '/');
            if (is_dir($dir)) {
                $this->loadTranslationsFrom($dir, (string) $handle);
                $this->loadJsonTranslationsFrom($dir);
            }
        }
    }

    private function bootMigrations(string $root, array $entry): void
    {
        foreach ($entry['migration_paths'] ?? [] as $rel) {
            $dir = $root . '/' . ltrim((string) $rel, '/');
            if (is_dir($dir)) {
                $this->loadMigrationsFrom($dir);
            }
        }
    }

    private function bootLivewire(string $root, array $entry): void
    {
        if (empty($entry['livewire_paths']) || !class_exists(Livewire::class)) {
            return;
        }
        $namespace = rtrim((string) ($entry['namespace'] ?? ''), '\\');
        foreach ($entry['livewire_paths'] as $rel) {
            $dir = $root . '/' . ltrim((string) $rel, '/');
            if (!is_dir($dir)) {
                continue;
            }
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
            foreach ($it as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                $class = str_replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''], str_replace($dir . DIRECTORY_SEPARATOR, '', $file->getPathname()));
                $fqcn  = $namespace . '\\' . $class;
                if (class_exists($fqcn)) {
                    Livewire::component(str_replace(['\\', '_'], ['.', '-'], strtolower($class)), $fqcn);
                }
            }
        }
    }

    private function bootBlaze(string $root, array $entry): void
    {
        if (!class_exists(Blaze::class)) {
            return;
        }
        $specs = ArtifactMatrix::blazeSpecs($entry);
        if (empty($specs)) {
            return;
        }
        $optimizer = Blaze::optimize();
        foreach ($specs as $spec) {
            $dir = $root . '/' . ltrim($spec['path'], '/');
            if (!is_dir($dir)) {
                continue;
            }
            if (!$spec['compile']) { $optimizer->in($dir, compile: false); continue; }
            if ($spec['fold']) { $optimizer->in($dir, true, ...array_filter(['safe' => $spec['safe'] ?: null, 'unsafe' => $spec['unsafe'] ?: null])); continue; }
            if ($spec['memo'])    { $optimizer->in($dir, memo: true); continue; }
            $optimizer->in($dir);
        }
    }

    /**
     * Boot and register console commands defined in the given entry.
     *
     * @param string               $root  Root path of the module or aptitude
     * @param array<string,mixed>  $entry Manifest entry containing configuration
     */
    private function bootCommands(string $root, array $entry): void
    {
        if (empty($entry['command_paths']) || !$this->app->runningInConsole()) {
            return;
        }

        /** @var list<class-string<Command>> $commands */
        $commands = [];

        foreach ($entry['command_paths'] as $rel) {
            $dir = $root . '/' . ltrim((string) $rel, '/');
            if (!is_dir($dir)) {
                continue;
            }

            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            /** @var SplFileInfo $file */
            foreach ($it as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $fqcn = extractClassFromFile($file->getPathname());

                if ($fqcn !== null && is_subclass_of($fqcn, \Illuminate\Console\Command::class)) {
                    $commands[] = $fqcn;
                }
            }
        }

        if (!empty($commands)) {
            $this->commands($commands);
        }
    }
}
