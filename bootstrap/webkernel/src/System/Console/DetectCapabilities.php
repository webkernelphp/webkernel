<?php declare(strict_types=1);

namespace Webkernel\System\Console;

use Illuminate\Console\Command;
use Webkernel\System\Support\CapabilityMap;

/**
 * Artisan command: detect host capabilities and write deployment.php.
 *
 * Usage:
 *   php artisan webkernel:detect-capabilities
 *   php artisan webkernel:detect-capabilities --force   # regenerate even if file exists
 *
 * Run automatically on fresh install via webkernel:install.
 * Re-run manually after server migrations or PHP version upgrades.
 */
final class DetectCapabilities extends Command
{
    protected $signature   = 'webkernel:detect-capabilities {--force : Regenerate even if file exists}';
    protected $description = 'Detect host capabilities and write the deployment profile file.';

    public function handle(): int
    {
        $path = base_path('deployment.php');

        if (is_file($path) && !$this->option('force')) {
            $this->line('deployment.php already exists. Use --force to regenerate.');
            return self::SUCCESS;
        }

        // Force live detection — ignore any cached instance
        CapabilityMap::reset();
        $cap = CapabilityMap::get();

        $profile = (string) env('WEBKERNEL_PROFILE', 'auto');

        $data = [
            'generated_at' => date('Y-m-d H:i:s'),
            'hostname'     => php_uname('n'),
            'php_version'  => PHP_VERSION,
            'profile'      => $profile,
            'capabilities' => [
                'proc_fs'         => $cap->hasProcFs,
                'ffi'             => $cap->hasFfi,
                'symfony_process' => $cap->hasSymfonyProcess,
                'opcache'         => $cap->hasOpcache,
                'shell_exec'      => $cap->shellExecAllowed,
            ],
            'cache' => [
                'host_metrics_ttl' => (int) env('WEBKERNEL_METRICS_TTL', 60),
                'static_ttl'       => 0,
            ],
            'features' => [
                'host_metrics'    => $cap->hasProcFs || $cap->hasSymfonyProcess,
                'fpm_metrics'     => $cap->hasProcFs,
                'process_metrics' => $cap->hasProcFs,
            ],
        ];

        $export = var_export($data, true);
        $tmp    = $path . '.tmp.' . getmypid();

        file_put_contents(
            $tmp,
            "<?php\n"
            . "// webkernel deployment profile — do not edit manually\n"
            . "// regenerate: php artisan webkernel:detect-capabilities --force\n"
            . "\nreturn {$export};\n"
        );

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
