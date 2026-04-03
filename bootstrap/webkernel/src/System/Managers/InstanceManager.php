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

/**
 * PHP process-level metrics manager.
 *
 * All DTOs are built once per manager instance and memoised.
 * The manager is registered as a singleton, so the cost is
 * paid at most once per Octane worker lifecycle.
 *
 * @internal  Resolved from the container. Type-hint InstanceManagerInterface.
 */
final class InstanceManager implements InstanceManagerInterface
{
    private ?InstanceMemoryInfoInterface $memory   = null;
    private ?OpcacheInfoInterface        $opcache  = null;
    private ?PhpInfoInterface            $php      = null;
    private ?PhpLimitsInfoInterface      $limits   = null;

    public function memory(): InstanceMemoryInfoInterface
    {
        return $this->memory ??= new InstanceMemoryInfo(
            phpMemoryUsed:  memory_get_usage(true),
            phpMemoryPeak:  memory_get_peak_usage(true),
            phpMemoryLimit: ByteFormatter::parse(ini_get('memory_limit') ?: '128M'),
        );
    }

    public function opcache(): OpcacheInfoInterface
    {
        return $this->opcache ??= new OpcacheInfo(
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
        return $this->php ??= new PhpInfo(
            version:        PHP_VERSION,
            sapi:           PHP_SAPI,
            iniFile:        php_ini_loaded_file() ?: null,
            extensionCount: count(get_loaded_extensions()),
        );
    }

    public function limits(): PhpLimitsInfoInterface
    {
        return $this->limits ??= new PhpLimitsInfo(
            maxExecutionTime:  (int) ini_get('max_execution_time'),
            uploadMaxFilesize: ByteFormatter::parse(ini_get('upload_max_filesize') ?: '2M'),
            postMaxSize:       ByteFormatter::parse(ini_get('post_max_size') ?: '8M'),
            maxInputVars:      (int) ini_get('max_input_vars'),
        );
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
