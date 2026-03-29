<?php declare(strict_types=1);

defined('WEBKERNEL_MIN_PHP_SERIES')             || define('WEBKERNEL_MIN_PHP_SERIES',             '8.2');
defined('WEBKERNEL_MIN_PHP')                    || define('WEBKERNEL_MIN_PHP',                     WEBKERNEL_COMPATIBLE_WITH['php']);
defined('WEBKERNEL_RECOMMENDED_PHP_SERIES')     || define('WEBKERNEL_RECOMMENDED_PHP_SERIES',     '8.4');
defined('WEBKERNEL_RECOMMENDED_PHP')            || define('WEBKERNEL_RECOMMENDED_PHP',             WEBKERNEL_COMPATIBLE_WITH['php']);
defined('WEBKERNEL_MIN_COMPOSER')               || define('WEBKERNEL_MIN_COMPOSER',               '2.5.0');
defined('WEBKERNEL_RECOMMENDED_COMPOSER')       || define('WEBKERNEL_RECOMMENDED_COMPOSER',       '2.8.0');
defined('WEBKERNEL_MIN_LARAVEL')                || define('WEBKERNEL_MIN_LARAVEL',                 WEBKERNEL_COMPATIBLE_WITH['laravel']);
defined('WEBKERNEL_MIN_FILAMENT')               || define('WEBKERNEL_MIN_FILAMENT',                WEBKERNEL_COMPATIBLE_WITH['filament']);
defined('WEBKERNEL_MIN_LIVEWIRE')               || define('WEBKERNEL_MIN_LIVEWIRE',               '3.5.0');

defined('WEBKERNEL_MIN_MEMORY_LIMIT_MB')        || define('WEBKERNEL_MIN_MEMORY_LIMIT_MB',        128);
defined('WEBKERNEL_REC_MEMORY_LIMIT_MB')        || define('WEBKERNEL_REC_MEMORY_LIMIT_MB',        512);
defined('WEBKERNEL_MIN_MAX_EXEC_TIME')          || define('WEBKERNEL_MIN_MAX_EXEC_TIME',           30);
defined('WEBKERNEL_REC_MAX_EXEC_TIME')          || define('WEBKERNEL_REC_MAX_EXEC_TIME',          120);
defined('WEBKERNEL_MIN_UPLOAD_MAX_MB')          || define('WEBKERNEL_MIN_UPLOAD_MAX_MB',           32);
defined('WEBKERNEL_MIN_POST_MAX_MB')            || define('WEBKERNEL_MIN_POST_MAX_MB',             32);
defined('WEBKERNEL_DISK_CRITICAL_BYTES')        || define('WEBKERNEL_DISK_CRITICAL_BYTES',        1_073_741_824);
defined('WEBKERNEL_DISK_WARNING_BYTES')         || define('WEBKERNEL_DISK_WARNING_BYTES',         5_368_709_120);

defined('WEBKERNEL_SLOW_REQUEST_MS')            || define('WEBKERNEL_SLOW_REQUEST_MS',            500);
defined('WEBKERNEL_SLOW_QUERY_MS')              || define('WEBKERNEL_SLOW_QUERY_MS',              100);
defined('WEBKERNEL_SLOW_JOB_MS')                || define('WEBKERNEL_SLOW_JOB_MS',               5000);

defined('WEBKERNEL_REQUIRED_EXTENSIONS') || define('WEBKERNEL_REQUIRED_EXTENSIONS', [
    'openssl', 'pdo', 'mbstring', 'tokenizer', 'xml', 'ctype',
    'json', 'bcmath', 'curl', 'fileinfo', 'gd', 'intl', 'zip', 'sodium',
]);

defined('WEBKERNEL_CRITICAL_EXTENSIONS') || define('WEBKERNEL_CRITICAL_EXTENSIONS', [
    'openssl'  => 'TLS and cryptographic operations',
    'sodium'   => 'Authenticated encryption — mandatory for medical, government, financial deployments',
    'pdo'      => 'Database abstraction layer',
    'mbstring' => 'Multi-byte string handling — mandatory for internationalisation',
    'json'     => 'JSON encoding and decoding',
    'curl'     => 'HTTP client — required for remote integrity verification',
]);
