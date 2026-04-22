<?php declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Metrics poll interval (milliseconds)
    |--------------------------------------------------------------------------
    |
    | How frequently the SystemPulseWidget Livewire component refreshes.
    | Minimum recommended: 2000 ms.
    |
    */
    'metrics_poll_ms' => (int) env('WEBKERNEL_METRICS_POLL_MS', 5000),

    /*
    |--------------------------------------------------------------------------
    | Disk warning / critical thresholds
    |--------------------------------------------------------------------------
    |
    | Override the constants defined in fast-boot.php if you need
    | per-instance thresholds loaded at Laravel boot time.
    |
    */
    'disk_critical_bytes' => (int) env('WEBKERNEL_DISK_CRITICAL_BYTES', WEBKERNEL_DISK_CRITICAL_BYTES),
    'disk_warning_bytes'  => (int) env('WEBKERNEL_DISK_WARNING_BYTES',  WEBKERNEL_DISK_WARNING_BYTES),

    /*
    |--------------------------------------------------------------------------
    | Security: production mode metric masking
    |--------------------------------------------------------------------------
    |
    | When true, sensitive values (IP, paths, patch versions) are masked
    | for unauthenticated or non-admin contexts regardless of APP_ENV.
    |
    */
    'force_production_masking' => (bool) env('WEBKERNEL_FORCE_MASKING', false),

    /*
    |--------------------------------------------------------------------------
    | PHP releases cache TTL override
    |--------------------------------------------------------------------------
    |
    | Seconds before the php.net releases cache is considered stale.
    | Defaults to the WEBKERNEL_PHP_RELEASES_TTL constant (86400).
    |
    */
    'php_releases_ttl' => (int) env('WEBKERNEL_PHP_RELEASES_TTL', WEBKERNEL_PHP_RELEASES_TTL),

];
