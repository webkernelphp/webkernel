<?php declare(strict_types=1);

namespace Webkernel\System\Host\Support;

/**
 * Process-level singleton holding detected host capabilities.
 *
 * Built once per Octane worker (or once per FPM process) on first access.
 * Reading deployment.php is ~0.1ms when the file is present.
 * Live detection (file missing) runs 5 safe probes at ~1–2ms total.
 *
 * Call CapabilityMap::reset() in the Octane WorkerStarting hook to rebuild
 * for each new worker, picking up any regenerated deployment.php.
 */
final class CapabilityMap
{
    private static ?self $instance = null;

    private function __construct(
        public readonly bool   $hasProcFs,
        public readonly bool   $hasFfi,
        public readonly bool   $hasSymfonyProcess,
        public readonly bool   $hasOpcache,
        public readonly bool   $shellExecAllowed,
        public readonly int    $hostMetricsTtl,
        public readonly bool   $hostMetricsEnabled,
        public readonly bool   $fpmMetricsEnabled,
        public readonly bool   $processMetricsEnabled,
        public readonly string $profile,
    ) {}

    public static function get(): self
    {
        return self::$instance ??= self::build();
    }

    /**
     * Force a rebuild on the next call to get().
     * Call this in Octane's WorkerStarting event so each worker re-reads
     * deployment.php and re-probes capabilities cleanly.
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    // ── Build ─────────────────────────────────────────────────────────────────

    private static function build(): self
    {
        $deploymentPath = base_path('deployment.php');
        $cfg = is_file($deploymentPath) ? (require $deploymentPath) : [];

        // Verify the file is still valid for this host; fall back to live detection on mismatch
        $storedHost = $cfg['hostname'] ?? null;
        $storedPhp  = $cfg['php_version'] ?? null;

        if ($storedHost !== null && $storedHost !== php_uname('n')) {
            \Illuminate\Support\Facades\Log::debug('webkernel: deployment.php hostname mismatch, re-detecting');
            $cfg = [];
        } elseif ($storedPhp !== null && $storedPhp !== PHP_VERSION) {
            \Illuminate\Support\Facades\Log::debug('webkernel: deployment.php php_version mismatch, re-detecting');
            $cfg = [];
        }

        $cap = $cfg['capabilities'] ?? [];

        return new self(
            hasProcFs:             $cap['proc_fs']         ?? self::probeProcFs(),
            hasFfi:                $cap['ffi']             ?? self::probeFfi(),
            hasSymfonyProcess:     $cap['symfony_process'] ?? self::probeSymfonyProcess(),
            hasOpcache:            $cap['opcache']         ?? extension_loaded('Zend OPcache'),
            shellExecAllowed:      $cap['shell_exec']      ?? self::probeShellExec(),
            hostMetricsTtl:        (int)    ($cfg['cache']['host_metrics_ttl'] ?? 60),
            hostMetricsEnabled:    (bool)   ($cfg['features']['host_metrics']    ?? true),
            fpmMetricsEnabled:     (bool)   ($cfg['features']['fpm_metrics']     ?? true),
            processMetricsEnabled: (bool)   ($cfg['features']['process_metrics'] ?? true),
            profile:               (string) ($cfg['profile'] ?? 'auto'),
        );
    }

    // ── Safe probes ───────────────────────────────────────────────────────────

    private static function probeProcFs(): bool
    {
        // Check open_basedir before touching the filesystem
        $openBasedir = ini_get('open_basedir');
        if ($openBasedir !== false && $openBasedir !== '') {
            $allowed = false;
            foreach (explode(PATH_SEPARATOR, $openBasedir) as $dir) {
                $dir = rtrim($dir, '/\\');
                if ($dir === '/proc' || str_starts_with('/proc', $dir . '/')) {
                    $allowed = true;
                    break;
                }
            }
            if (!$allowed) {
                return false;
            }
        }

        $prev = set_error_handler(static fn() => true);
        try {
            return is_dir('/proc') && is_readable('/proc/loadavg');
        } catch (\Throwable) {
            return false;
        } finally {
            set_error_handler($prev);
        }
    }

    private static function probeFfi(): bool
    {
        if (!extension_loaded('ffi')) {
            return false;
        }
        $setting = strtolower(trim((string) ini_get('ffi.enable')));
        return in_array($setting, ['true', '1', 'on', 'yes'], true);
    }

    private static function probeSymfonyProcess(): bool
    {
        if (!class_exists(\Symfony\Component\Process\Process::class)) {
            return false;
        }
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        return !in_array('shell_exec', $disabled, true)
            && !in_array('proc_open', $disabled, true);
    }

    private static function probeShellExec(): bool
    {
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        return !in_array('shell_exec', $disabled, true);
    }
}
