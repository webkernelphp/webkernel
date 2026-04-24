<?php declare(strict_types=1);

namespace Webkernel\System\Host\Console;

use Illuminate\Console\Command;
use Webkernel\Query\Traits\Exportable;
use Webkernel\Query\Traits\FileSystemHelpers;
use Webkernel\System\Host\Support\CapabilityMap;

/**
 * Artisan command: detect host capabilities and write deployment.php.
 *
 * Usage:
 *   php artisan webkernel:detect-capabilities
 *   php artisan webkernel:detect-capabilities --force   # regenerate even if file exists
 *
 * Run automatically on fresh install via webkernel:install.
 * Re-run manually after server migrations, PHP version upgrades, or open_basedir changes.
 *
 * The generated deployment.php is safe to edit — capabilities, feature flags, and TTLs
 * can all be overridden manually. Use --force to regenerate from live detection.
 */
final class DetectCapabilities extends Command
{
    use Exportable, FileSystemHelpers;

    protected $signature   = 'webkernel:detect-capabilities {--force : Regenerate even if file exists}';
    protected $description = 'Detect host capabilities and write the deployment profile file.';

    public function handle(): int
    {
        $path = base_path('deployment.php');

        if (is_file($path) && !$this->option('force')) {
            $this->line('deployment.php already exists. Use --force to regenerate.');
            return self::SUCCESS;
        }

        // Force live detection — ignore any cached singleton
        CapabilityMap::reset();
        $cap = CapabilityMap::get();

        $data = [
            'generated_at' => date('Y-m-d H:i:s'),
            'hostname'     => php_uname('n'),
            'php_version'  => PHP_VERSION,

            // shared | vps | dedicated | container | auto
            // Override via WEBKERNEL_PROFILE env variable.
            'profile' => (string) env('WEBKERNEL_PROFILE', 'auto'),

            // You may set any capability to true/false to override auto-detection.
            'capabilities' => [
                'proc_fs'         => $cap->hasProcFs,
                'ffi'             => $cap->hasFfi,
                'symfony_process' => $cap->hasSymfonyProcess,
                'opcache'         => $cap->hasOpcache,
                'shell_exec'      => $cap->shellExecAllowed,
            ],

            // host_metrics_ttl: seconds before re-reading CPU/RAM from /proc (0 = live).
            // static_ttl: 0 = process-lifetime (PHP version, cores, etc. never re-read).
            'cache' => [
                'host_metrics_ttl' => (int) env('WEBKERNEL_METRICS_TTL', 60),
                'static_ttl'       => 0,
            ],

            // Set any feature to false to disable its widget and skip all reads.
            'features' => [
                'host_metrics'    => $cap->hasProcFs || $cap->hasSymfonyProcess,
                'fpm_metrics'     => $cap->hasProcFs,
                'process_metrics' => $cap->hasProcFs,
            ],
        ];

        $export = $this->phpExport($data);
        $tmp    = $path . '.tmp.' . getmypid();

        $header  = "<?php\n";
        $header .= "// Webkernel deployment profile\n";
        $header .= "// Generated: " . $data['generated_at'] . " on " . $data['hostname'] . "\n";
        $header .= "//\n";
        $header .= "// You may edit this file to override capability detection.\n";
        $header .= "// Regenerate from scratch: php artisan webkernel:detect-capabilities --force\n";
        $header .= "\nreturn {$export};\n";

        $this->ensureDirectory(dirname($path));
        $this->writeFile($tmp, $header);
        rename($tmp, $path);

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }

        $this->info("Deployment profile written to: {$path}");
        $this->newLine();
        $this->table(['Capability', 'Available'], [
            ['proc_fs',         $cap->hasProcFs         ? '✓' : '✗'],
            ['ffi',             $cap->hasFfi            ? '✓' : '✗'],
            ['symfony_process', $cap->hasSymfonyProcess ? '✓' : '✗'],
            ['opcache',         $cap->hasOpcache        ? '✓' : '✗'],
            ['shell_exec',      $cap->shellExecAllowed  ? '✓' : '✗'],
        ]);
        $this->newLine();
        $this->table(['Feature', 'Enabled'], [
            ['host_metrics',    $data['features']['host_metrics']    ? '✓' : '✗'],
            ['fpm_metrics',     $data['features']['fpm_metrics']     ? '✓' : '✗'],
            ['process_metrics', $data['features']['process_metrics'] ? '✓' : '✗'],
        ]);

        return self::SUCCESS;
    }
}
