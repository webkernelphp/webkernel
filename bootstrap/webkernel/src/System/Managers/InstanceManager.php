<?php declare(strict_types=1);

namespace Webkernel\System\Managers;

use Webkernel\System\Contracts\Info\InstanceMemoryInfoInterface;
use Webkernel\System\Contracts\Info\OpcacheInfoInterface;
use Webkernel\System\Contracts\Info\PhpInfoInterface;
use Webkernel\System\Contracts\Info\PhpLimitsInfoInterface;
use Webkernel\System\Contracts\Managers\InstanceManagerInterface;
use Webkernel\System\Dto\InstanceMemoryInfo;
use Webkernel\System\Dto\OpcacheInfo;
use Webkernel\System\Dto\PhpInfo;
use Webkernel\System\Dto\PhpLimitsInfo;
use Webkernel\System\Support\ByteFormatter;
use Webkernel\System\Support\OpcacheReader;
use Webkernel\System\Support\StaticDataCache;

/**
 * PHP process-level metrics manager.
 *
 * Data classification:
 *   - php(), limits()   → process-lifetime static (StaticDataCache, cost zero after 1st read)
 *   - opcache()         → live per call (hit ratio changes per request under FPM)
 *   - memory()          → live per call (memory_get_usage() changes within the request)
 *
 * @internal  Resolved from the container. Type-hint InstanceManagerInterface.
 */
final class InstanceManager implements InstanceManagerInterface
{
    public function memory(): InstanceMemoryInfoInterface
    {
        // No caching — memory_get_usage(true) changes during the request lifecycle
        return new InstanceMemoryInfo(
            phpMemoryUsed:  memory_get_usage(true),
            phpMemoryPeak:  memory_get_peak_usage(true),
            phpMemoryLimit: StaticDataCache::remember(
                'php.memory_limit',
                fn() => ByteFormatter::parse(ini_get('memory_limit') ?: '128M'),
            ),
        );
    }

    public function opcache(): OpcacheInfoInterface
    {
        // No caching — OPcache stats (hit ratio, cached scripts) change per request
        return new OpcacheInfo(
            enabled:          OpcacheReader::isEnabled(),
            hitRatio:         OpcacheReader::hitRatio(),
            cachedScripts:    OpcacheReader::cachedScripts(),
            memoryUsed:       OpcacheReader::memoryUsed(),
            memoryFree:       OpcacheReader::memoryFree(),
            wastedPercentage: OpcacheReader::wastedPercentage(),
        );
    }

    public function php(): PhpInfoInterface
    {
        return StaticDataCache::remember('php.info', fn() => new PhpInfo(
            version:        PHP_VERSION,
            sapi:           PHP_SAPI,
            iniFile:        php_ini_loaded_file() ?: null,
            extensionCount: count(get_loaded_extensions()),
        ));
    }

    public function limits(): PhpLimitsInfoInterface
    {
        return StaticDataCache::remember('php.limits', fn() => new PhpLimitsInfo(
            maxExecutionTime:  (int) ini_get('max_execution_time'),
            uploadMaxFilesize: ByteFormatter::parse(ini_get('upload_max_filesize') ?: '2M'),
            postMaxSize:       ByteFormatter::parse(ini_get('post_max_size') ?: '8M'),
            maxInputVars:      (int) ini_get('max_input_vars'),
        ));
    }

    public function environment(): string
    {
        return (string) app()->environment();
    }

    public function debug(): bool
    {
        return (bool) config('app.debug', false);
    }
}
