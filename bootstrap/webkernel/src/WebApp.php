<?php declare(strict_types=1);
namespace Webkernel;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\ApplicationBuilder;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Webkernel\Exceptions\Handler as WebkernelExceptionHandler;
use Webkernel\BackOffice\Installer\Presentation\Installer\InstallationState;
use Webkernel\System\Security\CoreManifest;
use Webkernel\System\Security\SealEnforcer;

final class WebApp extends Application
{
    private static bool    $integrityBooted        = false;
    private static ?string $lastManifestFingerprint = null;
    private static ?string $webkernelVersion        = null;
    private static ?array  $instanceData            = null;

    // -------------------------------------------------------------------------

    public function __construct($basePath = null)
    {
        parent::__construct($basePath);

        if (! $this->bound('config')) {
            $this->instance('config', new ConfigRepository([]));
        }
    }

    // -------------------------------------------------------------------------

    public static function bootstrapCoreIntegrity(string $basePath): void
    {
        $status      = CoreManifest::verify(basePath: $basePath, manifestPath: WEBKERNEL_CACHE_PATH_MANIFEST);
        $fingerprint = $status['fingerprint'] ?? null;

        if (! self::$integrityBooted) {
            class_exists(SealEnforcer::class);
            spl_autoload_register(static fn(string $c) => SealEnforcer::inspect($c), prepend: true);
            SealEnforcer::boot(paranoid: true, trustedBasePath: WEBKERNEL_PATH);
            self::$integrityBooted        = true;
            self::$lastManifestFingerprint = $fingerprint;
            return;
        }

        if (is_string($fingerprint) && self::$lastManifestFingerprint !== $fingerprint) {
            SealEnforcer::reload(paranoid: true, trustedBasePath: WEBKERNEL_PATH);
            self::$lastManifestFingerprint = $fingerprint;
        }
    }

    // -------------------------------------------------------------------------

    public static function configure(?string $basePath = null, string $version = 'dev'): ApplicationBuilder
    {
        $basePath = \is_string($basePath) ? $basePath : static::inferBasePath();
        self::$webkernelVersion = $version;
        self::bootstrapCoreIntegrity($basePath);

        $app = new static($basePath);
        $app->rebinding('request', static fn() => self::bootstrapCoreIntegrity($basePath));

        $builder = (new ApplicationBuilder($app))
            ->withKernels()
            ->withEvents()
            ->withProviders([
                \Webkernel\ServiceProvider::class,
                \Webkernel\Arcanes\Modules::class,
                \Webkernel\Arcanes\Commands\DeclareCommands::class,
                \Webkernel\BackOffice\Installer\Providers\InstallerPanelProvider::class,
            ])
            ->withCommands()
            ->withRouting(
                web: WEBKERNEL_PATH . '/platform/routing/routes/web.php',
                api: WEBKERNEL_PATH . '/platform/routing/routes/api.php',
                commands: WEBKERNEL_PATH . '/platform/routing/routes/console.php',
                health: '/up',
            )
            ->withMiddleware(function (Middleware $m): void {
                $m->prepend(InstallationGuard::class);
            })
            //->withExceptions(fn(Exceptions $e): null => null);

            ->withExceptions(function (Exceptions $exceptions) {
                // This ensures that right before an exception is rendered,
                // the 'errors' namespace points to your custom directory.
                $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
                    $path = WEBKERNEL_ERRORS_PAGES_PATH;

                    // Force the namespace right now
                    View::replaceNamespace('errors', [$path]);

                    $status = $e->getStatusCode();

                    if (view()->exists("errors::{$status}")) {
                        return response()->view("errors::{$status}", [
                            'exception' => $e,
                        ], $status);
                    }
                });
            });

        return $builder;
    }

    // -------------------------------------------------------------------------

    public function webkernelVersion(): string
    {
        return self::$webkernelVersion ?? 'dev';
    }

    public function webkernelInstance(): array
    {
        if (self::$instanceData === null) {
            $file = defined('WEBKERNEL_INSTANCE_FILE')
                ? WEBKERNEL_INSTANCE_FILE
                : $this->storagePath('webkernel/instance.json');

            self::$instanceData = is_file($file)
                ? (json_decode(file_get_contents($file), true) ?? [])
                : [];
        }

        return self::$instanceData;
    }

    public function webkernelInstanceAttribute(string $key, mixed $default = null): mixed
    {
        return data_get($this->webkernelInstance(), 'data.attributes.' . $key, $default);
    }

    // -------------------------------------------------------------------------

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
    public function handle(\Illuminate\Http\Request $request, \Closure $next): mixed
    {
        $isHtmlRequest   = str_starts_with($request->header('Accept', ''), 'text/html');
        $isInstallerPath = str_starts_with($request->decodedPath(), WEBKERNEL_INSTALLER_PATH_PREFIX);
        $isHealthPath    = $request->decodedPath() === WEBKERNEL_HEALTH_PATH;

        if ($isHtmlRequest && ! $isInstallerPath && ! $isHealthPath) {
            $state = InstallationState::resolve();
            if ($state !== InstallationState::INSTALLED) {
                return redirect(WEBKERNEL_INSTALLER_URL);
            }
        }

        return $next($request);
    }
}
