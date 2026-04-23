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
 * Sole owner of module and platform-capability discovery, caching, and booting.
 *
 * Responsibility boundary: this class knows nothing about platform integrity.
 * Core file verification is handled exclusively by Webkernel\System\Security\CoreManifest
 * (alongside SealEnforcer), and runs before any provider is registered.
 * Arcanes\Modules only runs after that gate has passed.
 *
 * Discovery sources
 * -----------------
 * Two distinct kinds of artifacts are discovered and merged into a single catalog:
 *
 *   1. External modules  -- rooted at WEBKERNEL_MODULES_ROOT (BASE_PATH/modules).
 *      Directory layout:  <registry>/<vendor>/<slug>/module.php
 *      Namespace prefix:  WebModule\
 *
 *   2. Platform capabilities -- internal reusable code that lives directly under
 *      the Webkernel\ namespace (e.g. Webkernel\Panels, Webkernel\Plugins).
 *      Each root listed in WEBKERNEL_PLATFORM_LOCATIONS is scanned; immediate
 *      sub-directories that contain a platform.php manifest are loaded.
 *      Manifest file: platform.php
 *      _type value:   'platform'
 *
 *      Adding a new capability root requires only appending its path to
 *      WEBKERNEL_PLATFORM_LOCATIONS in paths.php -- no code change here.
 *
 * All artifact-kind-specific strings (manifest filenames, namespace rules,
 * id formats) are declared in support/constants/arcanes.php and read at
 * runtime through constants.  No kind name is hardcoded in this class.
 *
 * Cache lifecycle: SHA-256 fingerprint of all manifest mtime+size.
 * Stale or missing cache rebuilds synchronously under a file lock.
 * Cache invalidation is automatic -- no artisan command needed.
 *
 * @since Webkernel Waterfall (v1.x.x)
 */
final class Modules extends ServiceProvider
{
    use CacheLock, Exportable, FileSystemHelpers, LoggerTrait;

    // -------------------------------------------------------------------------
    // ServiceProvider lifecycle
    // -------------------------------------------------------------------------

    public function register(): void
    {
        $this->ensureDirectory(dirname(WEBKERNEL_MODULES_CACHE));

        foreach ($this->catalog() as $entry) {
            if (!($entry['active'] ?? false)) {
                continue;
            }
            foreach ($entry['providers'] ?? [] as $provider) {
                try {
                    if (class_exists($provider)) {
                        $this->app->register($provider);
                    }
                } catch (\Throwable $e) {
                    $this->warn("Provider [{$provider}] failed to load: " . $e->getMessage());
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

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    public static function makeId(string $registry, string $vendor, string $slug): string
    {
        return NamingHelper::buildId('module', $registry, $vendor, $slug);
    }

    public static function makePlatformId(string $slug, string $scope = ''): string
    {
        $scope = $scope !== '' ? $scope : (defined('WEBKERNEL_PLATFORM_DEFAULT_SCOPE') ? WEBKERNEL_PLATFORM_DEFAULT_SCOPE : 'platform');
        return NamingHelper::buildId('platform', $scope, $slug);
    }

    // -------------------------------------------------------------------------
    // Cache
    // -------------------------------------------------------------------------

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
        $all     = array_merge($this->discoverPlatform(), $this->discoverModules());
        $valid   = $this->validate($all);
        $ordered = $this->dependencyOrder($valid);
        $this->writeCache($ordered, $reason);

        $ms     = round((hrtime(true) - $start) / 1_000_000, 2);
        $active = count(array_filter($ordered, static fn ($e) => (bool) ($e['active'] ?? false)));
        $this->warn("Cache rebuilt [{$reason}] in {$ms}ms -- {$active}/" . count($ordered) . " active.");

        return $ordered;
    }

    // -------------------------------------------------------------------------
    // Discovery
    // -------------------------------------------------------------------------

    /**
     * Discover all platform capabilities from every root listed in
     * WEBKERNEL_PLATFORM_LOCATIONS.
     *
     * Each root is an absolute directory whose immediate children are scanned
     * for the manifest file declared in WEBKERNEL_MANIFEST_FILES['platform'].
     * The scope segment of the id defaults to WEBKERNEL_PLATFORM_DEFAULT_SCOPE
     * but can be overridden per-manifest via the '_scope' key.
     *
     * @return array<string, array<string, mixed>>
     */
    private function discoverPlatform(): array
    {
        $locations    = WEBKERNEL_PLATFORM_LOCATIONS;
        $manifestFile = $this->manifestFile('platform');
        $defaultScope = defined('WEBKERNEL_PLATFORM_DEFAULT_SCOPE')
            ? WEBKERNEL_PLATFORM_DEFAULT_SCOPE
            : 'platform';

        if (empty($locations)) {
            return [];
        }

        $found = [];

        foreach ($locations as $root) {
            if (!is_dir($root)) {
                continue;
            }

            foreach (glob($root . '/*', GLOB_ONLYDIR) ?: [] as $path) {
                $file = $path . '/' . $manifestFile;
                if (!is_file($file)) {
                    continue;
                }

                $manifest = require $file;
                if (!is_array($manifest)) {
                    continue;
                }

                $folder    = basename($path);
                $slug      = NamingHelper::slugFromFolder($folder);
                $scope     = (string) ($manifest['_scope'] ?? $defaultScope);
                $id        = NamingHelper::buildId('platform', $scope, $slug);
                $namespace = NamingHelper::normalizeNamespace(
                    (string) ($manifest['namespace'] ?? ''),
                    'platform',
                    $slug
                );

                $found[$id] = ArtifactMatrix::normalize(array_merge($manifest, [
                    'id'        => $id,
                    'slug'      => $slug,
                    '_root'     => $path,
                    '_type'     => 'platform',
                    '_scope'    => $scope,
                    '_slug'     => $slug,
                    'namespace' => $namespace,
                ]));
            }
        }

        return $found;
    }

    /** @return array<string, array<string, mixed>> */
    private function discoverModules(): array
    {
        $root         = WEBKERNEL_MODULES_ROOT;
        $manifestFile = $this->manifestFile('module');

        if (!is_dir($root)) {
            return [];
        }

        $found = [];

        foreach ($this->sortedRegistries($root) as $registryPath) {
            $registry = basename($registryPath);

            foreach (glob($registryPath . '/*', GLOB_ONLYDIR) ?: [] as $vendorPath) {
                foreach (glob($vendorPath . '/*', GLOB_ONLYDIR) ?: [] as $modulePath) {
                    $file = $modulePath . '/' . $manifestFile;
                    if (!is_file($file)) {
                        continue;
                    }

                    $manifest = require $file;
                    if (!is_array($manifest)) {
                        continue;
                    }

                    $vendor    = basename($vendorPath);
                    $slug      = basename($modulePath);
                    $id        = NamingHelper::buildId('module', $registry, $vendor, $slug);
                    $namespace = NamingHelper::normalizeNamespace(
                        (string) ($manifest['namespace'] ?? ''),
                        'module',
                        $slug,
                        $vendor
                    );

                    $found[$id] = ArtifactMatrix::normalize(array_merge($manifest, [
                        'id'        => $id,
                        '_root'     => $modulePath,
                        '_type'     => 'module',
                        '_registry' => $registry,
                        '_vendor'   => $vendor,
                        '_slug'     => $slug,
                        'namespace' => $namespace,
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

    /**
     * Return the manifest filename for a given artifact kind.
     * Falls back to '<type>.php' when the constant is not defined.
     */
    private function manifestFile(string $type): string
    {
        $map = defined('WEBKERNEL_MANIFEST_FILES') ? WEBKERNEL_MANIFEST_FILES : [];
        return $map[$type] ?? ($type . '.php');
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, array<string, mixed>> $all
     * @return array<string, array<string, mixed>>
     */
    private function validate(array $all): array
    {
        $nsRules = defined('WEBKERNEL_NAMESPACE_RULES') ? WEBKERNEL_NAMESPACE_RULES : [];
        $valid   = [];

        foreach ($all as $id => $entry) {
            $type    = $entry['_type'] ?? 'module';
            $missing = array_diff(ArtifactMatrix::required($type), array_keys($entry));

            if (!empty($missing)) {
                $this->warn("Entry [{$id}] rejected -- missing: " . implode(', ', $missing));
                continue;
            }

            if (!is_bool($entry['active'])) {
                $this->warn("Entry [{$id}] rejected -- 'active' must be boolean.");
                continue;
            }

            // Namespace prefix check -- driven entirely by WEBKERNEL_NAMESPACE_RULES.
            if (isset($nsRules[$type])) {
                $required = $nsRules[$type];
                if (!str_starts_with((string) ($entry['namespace'] ?? ''), $required)) {
                    $this->warn("Entry [{$id}] rejected -- namespace must start with {$required}");
                    continue;
                }
            }

            $valid[$id] = $entry;
        }

        return $valid;
    }

    // -------------------------------------------------------------------------
    // Dependency sort (Kahn topological)
    // -------------------------------------------------------------------------

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

    // -------------------------------------------------------------------------
    // Cache write
    // -------------------------------------------------------------------------

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

            $content = "<?php\n/** Webkernel catalog -- {$now} | v" . ArtifactMatrix::CACHE_PROTOCOL_VERSION . " | {$reason} | {$active}/{$total} */\nreturn " . $this->phpExport($payload) . ";\n";
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
        $paths        = [];
        $manifestFiles = defined('WEBKERNEL_MANIFEST_FILES') ? WEBKERNEL_MANIFEST_FILES : [];

        // External modules: <registry>/<vendor>/<slug>/<manifestFile>
        $moduleManifest = $manifestFiles['module'] ?? 'module.php';
        if (is_dir(WEBKERNEL_MODULES_ROOT)) {
            $paths = array_merge(
                $paths,
                glob(WEBKERNEL_MODULES_ROOT . '/*/*/*/' . $moduleManifest) ?: []
            );
        }

        // Platform capabilities: <location>/<slug>/<manifestFile>
        $platformManifest = $manifestFiles['platform'] ?? 'platform.php';
        foreach (WEBKERNEL_PLATFORM_LOCATIONS as $root) {
            if (is_dir($root)) {
                $paths = array_merge($paths, glob($root . '/*/' . $platformManifest) ?: []);
            }
        }

        sort($paths);

        return hash('sha256', implode("\n", array_map(
            static fn ($p) => $p . '|' . (@filemtime($p) ?: 0) . '|' . (@filesize($p) ?: 0),
            $paths,
        )));
    }

    // -------------------------------------------------------------------------
    // Boot steps
    // -------------------------------------------------------------------------

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

            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($it as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                $class = str_replace(
                    [DIRECTORY_SEPARATOR, '.php'],
                    ['\\', ''],
                    str_replace($dir . DIRECTORY_SEPARATOR, '', $file->getPathname())
                );
                $fqcn = $namespace . '\\' . $class;
                if (class_exists($fqcn)) {
                    Livewire::component(
                        str_replace(['\\', '_'], ['.', '-'], strtolower($class)),
                        $fqcn
                    );
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

            if (!$spec['compile']) {
                $optimizer->in($dir, compile: false);
                continue;
            }
            if ($spec['fold']) {
                $optimizer->in($dir, true, ...array_filter([
                    'safe'   => $spec['safe']   ?: null,
                    'unsafe' => $spec['unsafe'] ?: null,
                ]));
                continue;
            }
            if ($spec['memo']) {
                $optimizer->in($dir, memo: true);
                continue;
            }
            $optimizer->in($dir);
        }
    }

    /**
     * Boot and register console commands defined in the given entry.
     *
     * @param string              $root  Root path of the module or platform capability
     * @param array<string,mixed> $entry Manifest entry containing configuration
     */
    private function bootCommands(string $root, array $entry): void
    {
        if (empty($entry['command_paths']) || !$this->app->runningInConsole()) {
            return;
        }

        /** @var list<class-string<\Illuminate\Console\Command>> $commands */
        $commands = [];

        foreach ($entry['command_paths'] as $rel) {
            $dir = $root . '/' . ltrim((string) $rel, '/');
            if (!is_dir($dir)) {
                continue;
            }

            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
            );

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
