<?php declare(strict_types=1);
namespace Webkernel;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\ApplicationBuilder;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\View;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Webkernel\Base\System\Security\CoreManifest;
use Webkernel\Base\System\Security\SealEnforcer;
use Webkernel\CP\Installer\Presentation\Installer\InstallationState;


/**
 * @see \Illuminate\Foundation\Application
 */
final class WebApp extends Application
{
    private static bool    $integrityBooted        = false;
    private static ?string $lastManifestFingerprint = null;
    private static ?string $webkernelVersion        = null;
    private static ?array  $instanceData            = null;

    // -------------------------------------------------------------------------

    /**
     * Boots the application container, ensuring the config repository
     * is always bound even before any service provider has run.
     *
     * @param string|null $basePath Absolute path to the application root.
     */
    public function __construct(?string $basePath = null)
    {
        parent::__construct($basePath);

        if (! $this->bound('config')) {
            $this->instance('config', new ConfigRepository([]));
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Verifies the core manifest and boots the SealEnforcer integrity layer.
     * On first call it registers the autoload inspector and marks the system
     * as booted. On subsequent calls it reloads the enforcer only when the
     * manifest fingerprint has changed, avoiding redundant work.
     *
     * @param string $basePath Absolute path to the application root used to
     *                         locate the manifest and trusted class paths.
     * @return void
     */
    public static function bootstrapCoreIntegrity(string $basePath): void
    {
        /** @var array{fingerprint?: string} $status */
        $status      = CoreManifest::verify(basePath: $basePath, manifestPath: WEBKERNEL_CACHE_MANIFEST);
        $fingerprint = $status['fingerprint'] ?? null;

        if (! self::$integrityBooted) {
            class_exists(SealEnforcer::class);
            spl_autoload_register(static fn(string $c) => SealEnforcer::inspect($c), prepend: true);
            SealEnforcer::boot(paranoid: true, trustedBasePath: WEBKERNEL_UPPERPATH);
            self::$integrityBooted        = true;
            self::$lastManifestFingerprint = $fingerprint;
            return;
        }

        if (is_string($fingerprint) && self::$lastManifestFingerprint !== $fingerprint) {
            SealEnforcer::reload(paranoid: true, trustedBasePath: WEBKERNEL_UPPERPATH);
            self::$lastManifestFingerprint = $fingerprint;
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Set the configuration directory.
     *
     * @param  string  $path
     * @return $this
     */
    public function useConfigPath($path)
    {
        $this->configPath = $path;
        return $this;
    }

    /**
     * Set the storage directory.
     *
     * @param  string  $path
     * @return $this
     */
    public function useStoragePath($path)
    {
        $this->storagePath = $path;
        return $this;
    }

    /**
     * Get the path to the resources directory.
     *
     * @param  string  $path
     * @return string
     */
    public function resourcePath($path = '')
    {
        return $this->joinPaths(WEBKERNEL_PATH . '/resources', $path);
    }


    /**
     * Set the .env directory.
     *
     * @param  string  $path
     * @return $this
     */
    public function useEnvironmentPath($path)
    {
        $this->environmentPath = $path;
        return $this;
    }


    /**
     * Set the Bootstrap directory.
     *
     * @param  string  $path
     * @return $this
     */
    public function useBootstrapPath($path)
    {
        $this->bootstrapPath = $path;
        $this->instance('path.bootstrap', $path);
        return $this;
    }

    /**
     * Primary entry point for building the application. Resolves the base path,
     * stores the running version, runs the integrity bootstrap, and wires up all
     * core service providers, routes, middleware, and exception handling.
     * Also re-runs integrity on every request rebinding so runtime tampering
     * is caught immediately.
     *
     * @param string|null $basePath Absolute path to the application root.
     *                              When null it is inferred automatically.
     * @param string      $version  The running Webkernel version string, stored
     *                              for later retrieval via webkernelVersion().
     * @return ApplicationBuilder   The fully configured builder instance ready
     *                              to be returned from bootstrap/app.php.
     */
    public static function configure(?string $basePath = null, string $version = 'dev'): ApplicationBuilder
    {
        $basePath = \is_string($basePath) ? $basePath : static::inferBasePath();
        self::$webkernelVersion = $version;
        self::bootstrapCoreIntegrity($basePath);

        $app = new static($basePath);
        $app->useConfigPath($basePath . '/platform/config');
        $app->useStoragePath($basePath . '/platform/storage');
        $app->useBootstrapPath($basePath . '/internal');
        $app->useEnvironmentPath($basePath . '/platform/storage');

        $app->rebinding('request', static fn() => self::bootstrapCoreIntegrity($basePath));

        $builder = (new ApplicationBuilder($app))
            ->withKernels()
            ->withEvents()
            ->withProviders([
                \Webkernel\ServiceProvider::class,
                \Webkernel\Base\Arcanes\Commands\DeclareCommands::class,
                \Webkernel\CP\Installer\Providers\InstallerPanelProvider::class,
            ])
            ->withCommands()
            ->withRouting(
                web: WEBKERNEL_UPPERPATH . '/routes/web.php',
                api: WEBKERNEL_UPPERPATH . '/routes/api.php',
                commands: WEBKERNEL_UPPERPATH . '/routes/console.php',
                health: '/up',
            )
            ->withMiddleware(function (Middleware $m) use ($app): void {
                if (! $app->runningInConsole()) {
                    $m->prepend(InstallationGuard::class);
                }
            })
            ->withExceptions(function (Exceptions $exceptions): void {
                // Forces the 'errors' view namespace to our custom pages directory
                // right before any HTTP exception is rendered, so standard Laravel
                // error views are never accidentally served.
                $exceptions->render(function (HttpExceptionInterface $e): ?\Illuminate\Http\Response {
                    $path = WEBKERNEL_ERRORS_PAGES_PATH;

                    View::replaceNamespace('errors', [$path]);

                    $status = $e->getStatusCode();

                    if (view()->exists("errors::{$status}")) {
                        return response()->view("errors::{$status}", [
                            'exception' => $e,
                        ], $status);
                    }

                    return null;
                });
            });

        return $builder;
    }

    // -------------------------------------------------------------------------

    /**
     * Returns the Webkernel version string that was passed to configure().
     * Falls back to 'dev' when called before configure() or in test contexts.
     *
     * @return string
     */
    public function webkernelVersion(): string
    {
        return self::$webkernelVersion ?? 'dev';
    }

    /**
     * Reads and caches the instance metadata from the deployment file.
     * The file path is taken from the WEBKERNEL_INSTANCE_FILE constant when
     * defined, otherwise it defaults to storage/deployment.php.
     *
     * @return array<string, mixed>
     */
    public function webkernelInstance(): array
    {
        if (self::$instanceData === null) {
            $file = WEBKERNEL_INSTANCE_FILE;
            if (is_file($file)) {
                $data = include $file;
                self::$instanceData = is_array($data) ? $data : [];
            } else {
                self::$instanceData = [];
            }
        }
        return self::$instanceData;
    }

    /**
     * Convenience accessor that drills into the instance metadata using
     * dot-notation to reach a value under data.attributes.{key}.
     * Useful for reading licence fields, instance name, region, etc.
     * without having to traverse the array manually.
     *
     * @param string $key     Dot-notation key relative to data.attributes.
     * @param mixed  $default Value returned when the key is absent.
     * @return mixed
     */
    public function webkernelInstanceAttribute(string $key, mixed $default = null): mixed
    {
        return data_get($this->webkernelInstance(), 'data.attributes.' . $key, $default);
    }

    // -------------------------------------------------------------------------

    /**
     * Resolves the application's root PHP namespace by scanning the PSR-4
     * autoload map in composer.json (and the bootstrap fallback) and matching
     * the registered source path against the application's app/ directory.
     * Overrides the Laravel default to support non-standard project layouts
     * where composer.json may live under bootstrap/.
     *
     * @throws RuntimeException When no matching namespace can be found.
     * @return string           The resolved namespace, e.g. "App\".
     */
    public function getNamespace(): string
    {
        if (! is_null($this->namespace)) {
            return $this->namespace;
        }

        $psr4 = $this->readComposerPsr4($this->basePath('composer.json'));

        if (empty($psr4) && is_file($fb = $this->basePath('bootstrap/composer.json'))) {
            $psr4 = $this->readComposerPsr4($fb);
        }

        foreach ($psr4 as $namespace => $path) {
            foreach ((array) $path as $candidate) {
                if (realpath($this->path()) === realpath($this->basePath($candidate))) {
                    return $this->namespace = $namespace;
                }
            }
        }

        throw new RuntimeException('Unable to detect application namespace.');
    }

    /**
     * Parses the autoload.psr-4 section out of a composer.json file.
     * Returns an empty array when the file does not exist or cannot be decoded,
     * so callers never have to guard against missing files themselves.
     *
     * @param string $path Absolute path to the composer.json file to read.
     * @return array<string, string|string[]> PSR-4 namespace-to-path map.
     */
    private function readComposerPsr4(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        return (array) data_get(json_decode(file_get_contents($path), true), 'autoload.psr-4');
    }
}

// -----------------------------------------------------------------------------

final class InstallationGuard
{
    /**
     * Intercepts every incoming HTML request and redirects to the installer
     * when the application has not yet been fully installed. API requests,
     * installer routes, and the health check endpoint are always let through
     * so they never get caught in the redirect loop.
     *
     * @param \Illuminate\Http\Request $request The incoming HTTP request.
     * @param \Closure                 $next    The next middleware in the pipeline.
     * @return mixed                            A redirect response or the result
     *                                          of the next middleware.
     */
    public function handle(\Illuminate\Http\Request $request, \Closure $next): mixed
    {
        $isHtmlRequest   = str_starts_with($request->header('Accept', ''), 'text/html');
        $isInstallerPath = str_starts_with($request->decodedPath(), WEBKERNEL_INSTALLER_PATH_PREFIX);
        $isHealthPath    = $request->decodedPath() === WEBKERNEL_HEALTH_PATH;

        if ($isHtmlRequest && ! $isInstallerPath && ! $isHealthPath) {
            $state = InstallationState::resolve();
            if ($state !== InstallationState::INSTALLED) {
                // We send the response and exit immediately to avoid any
                // termination logic that might depend on an APP_KEY which
                // is often missing at this stage of the installation.
                redirect(WEBKERNEL_INSTALLER_URL)->send();
                exit(0);
            }
        }

        return $next($request);
    }
}
