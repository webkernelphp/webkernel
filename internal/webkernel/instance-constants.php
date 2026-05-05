<?php declare(strict_types=1);

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 1: FOUNDATION PATHS
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * @const string BASE_PATH — Application root directory (must be defined before this file)
 */
if (!defined('BASE_PATH')) {
    throw new RuntimeException('BASE_PATH must be defined before including constants.php');
}

/**
 * @const string PLATFORM_DIR — Mutable platform directory
 */
define('PLATFORM_DIR', BASE_PATH . '/platform');


// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 2: STORAGE & RUNTIME PATHS
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * @const string PLATFORM_CONFIG_PATH — Configuration files
 */
define('PLATFORM_CONFIG_PATH', PLATFORM_DIR . '/config');

/**
 * @const string PLATFORM_STORAGE_PATH — Runtime storage (writable)
 */
define('PLATFORM_STORAGE_PATH', PLATFORM_DIR . '/storage');

/**
 * @const string PLATFORM_PACKAGES_PATH — Composer dependencies
 */
define('PLATFORM_PACKAGES_PATH', PLATFORM_DIR . '/packages');

/**
 * @const string ENV_PATH — Environment file location
 */
define('ENV_PATH', PLATFORM_STORAGE_PATH . '/.env');

/**
 * @const string ENV_PATH_TEMPLATE — Template for .env.example (read-once)
 */
define('ENV_PATH_TEMPLATE', (static function (): string {
    $projectTemplate = BASE_PATH . '/.env.example';
    if (is_file($projectTemplate)) {
        return $projectTemplate;
    }
    $kernelTemplate = WEBKERNEL_UPPERPATH . '/.env.example';
    if (is_file($kernelTemplate)) {
        return $kernelTemplate;
    }
    return '';
})());

/**
 * @const string WEBKERNEL_SQLITE_PATH — Primary SQLite database
 */
define('WEBKERNEL_SQLITE_PATH', PLATFORM_STORAGE_PATH . '/database.sqlite');

// Webkernel Configuration Constants
defined('WEBKERNEL_DB_DRIVER')       || define('WEBKERNEL_DB_DRIVER', 'sqlite');
defined('WEBKERNEL_DB_PREFIX')       || define('WEBKERNEL_DB_PREFIX', '');
defined('WEBKERNEL_DB_FOREIGN_KEYS') || define('WEBKERNEL_DB_FOREIGN_KEYS', true);
defined('WEBKERNEL_DB_TIMEOUT')      || define('WEBKERNEL_DB_TIMEOUT', null);
defined('WEBKERNEL_DB_JOURNAL')      || define('WEBKERNEL_DB_JOURNAL', null);
defined('WEBKERNEL_DB_SYNC')         || define('WEBKERNEL_DB_SYNC', null);
defined('WEBKERNEL_DB_TX_MODE')      || define('WEBKERNEL_DB_TX_MODE', 'DEFERRED');

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 3: CACHE & INTERNAL PATHS
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * @const string WEBKERNEL_CACHE_PATH — Runtime cache directory
 */
define('WEBKERNEL_CACHE_PATH', BASE_PATH . '/internal/cache');

/**
 * @const string WEBKERNEL_CACHE_MANIFEST — Core manifest cache
 */
define('WEBKERNEL_CACHE_MANIFEST', WEBKERNEL_CACHE_PATH . '/core.manifest.php');

/**
 * @const string WEBKERNEL_MODULES_CACHE — Module discovery cache
 */
define('WEBKERNEL_MODULES_CACHE', WEBKERNEL_CACHE_PATH . '/modules.php');

/**
 * @const string WEBKERNEL_MODULES_LOCK — Lock file for cache rebuilds
 */
define('WEBKERNEL_MODULES_LOCK', WEBKERNEL_CACHE_PATH . '/.modules.lock');

/**
 * @const string WEBKERNEL_PHP_RELEASES_CACHE — PHP release info cache
 */
define('WEBKERNEL_PHP_RELEASES_CACHE', WEBKERNEL_CACHE_PATH . '/php-releases.json');

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 4: MODULE & PLATFORM DISCOVERY
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * @const string WEBKERNEL_MODULES_ROOT — External modules root
 */
define('WEBKERNEL_MODULES_ROOT', BASE_PATH . '/modules');

/**
 * @const string[] WEBKERNEL_PLATFORM_LOCATIONS — Internal platform capability roots
 */
define('WEBKERNEL_PLATFORM_LOCATIONS', [
    WEBKERNEL_PATH . '/CP',
]);


defined('WEBKERNEL_PLATFORM_CMD_OVERRIDES')
    || define('WEBKERNEL_PLATFORM_CMD_OVERRIDES', WEBKERNEL_SUPPORT_PATH . '/boot/actions/commands-overrides.php');

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 5: MANIFEST & NAMING CONVENTIONS
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * @const string[] WEBKERNEL_MANIFEST_FILES — Manifest filenames per artifact kind
 */
define('WEBKERNEL_MANIFEST_FILES', [
    'module'   => 'module.php',
    'platform' => 'platform.php',
]);

/**
 * @const string[] WEBKERNEL_ID_FORMATS — ID format templates per artifact kind
 */
define('WEBKERNEL_ID_FORMATS', [
    'module'   => '%s::%s/%s',
    'platform' => 'webkernel::%s/%s',
]);

/**
 * @const string[] WEBKERNEL_NAMESPACE_DEFAULTS — Default namespace prefixes
 */
define('WEBKERNEL_NAMESPACE_DEFAULTS', [
    'module'   => 'WebModule\\',
    'platform' => 'Webkernel\\',
]);

/**
 * @const string[] WEBKERNEL_NAMESPACE_RULES — Namespace validation rules
 */
define('WEBKERNEL_NAMESPACE_RULES', [
    'module'   => 'WebModule\\',
    'platform' => 'Webkernel\\',
]);

/**
 * @const string[] WEBKERNEL_ARTIFACT_REQUIRED_KEYS — Required manifest fields per kind
 */
define('WEBKERNEL_ARTIFACT_REQUIRED_KEYS', [
    'module'   => ['id', 'namespace', 'version', 'vendor', 'slug', 'party', 'active', 'registry'],
    'platform' => ['label', 'version', 'active'],
]);

/**
 * @const string WEBKERNEL_OFFICIAL_REGISTRY — Official module registry name
 */
define('WEBKERNEL_OFFICIAL_REGISTRY', 'webkernelphp-com');

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 6: BOOTSTRAP & HELPER PATHS
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * @const string WEBKERNEL_HELPERS_ROOT — Global helpers directory
 */
define('WEBKERNEL_HELPERS_ROOT', WEBKERNEL_SUPPORT_PATH . '/platform-helpers');

/**
 * @const string WEBKERNEL_ERRORS_PAGES_PATH — Custom error page templates
 */
define('WEBKERNEL_ERRORS_PAGES_PATH', WEBKERNEL_SUPPORT_PATH . '/boot/_dist/error-pages');

/**
 * @const string WEBKERNEL_DIST_ROOT — Distribution assets
 */
define('WEBKERNEL_DIST_ROOT', WEBKERNEL_SUPPORT_PATH . '/dist');

/**
 * @const string WEBKERNEL_STATIC_ROOT — Static assets
 */
define('WEBKERNEL_STATIC_ROOT', WEBKERNEL_SUPPORT_PATH . '/static');

/**
 * @const string WEBKERNEL_ARCANES_ROOT — Arcanes subsystem root
 */
define('WEBKERNEL_ARCANES_ROOT', WEBKERNEL_PATH . '/Arcanes');

/**
 * @const string WEBKERNEL_PANELS_PATH — Built-in capabilities root
 */
define('WEBKERNEL_PANELS_PATH', WEBKERNEL_PATH . '/CP');

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 7: BOOT ASSET PATHS (consumed by Platform class)
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * @const string[] WEBKERNEL_HELPER_PATHS — Helper file directories
 */
define('WEBKERNEL_HELPER_PATHS', [
    WEBKERNEL_SUPPORT_PATH . '/platform-helpers',
]);

/**
 * @const array WEBKERNEL_ROUTE_PATHS — Route file specifications
 */
define('WEBKERNEL_ROUTE_PATHS', [
    ['group' => 'web', 'file' => WEBKERNEL_PATH . '/routes/web.php'],
    ['group' => 'api', 'file' => WEBKERNEL_PATH . '/routes/api.php'],
]);

/**
 * @const string WEBKERNEL_CONFIG_PATH — Core configuration directory
 */
define('WEBKERNEL_CONFIG_PATH', WEBKERNEL_PATH . '/config');

/**
 * @const string[] WEBKERNEL_VIEW_NAMESPACES — Blade view namespace mappings
 */
define('WEBKERNEL_VIEW_NAMESPACES', [
    'webkernel'             => WEBKERNEL_SUPPORT_PATH . '/views',
    'webkernel-quick-touch' => WEBKERNEL_PATH . '/Base/QuickTouch',
    'errors'                => WEBKERNEL_ERRORS_PAGES_PATH,
]);

/**
 * @const string[] WEBKERNEL_LANG_PATHS — Translation paths
 */
define('WEBKERNEL_LANG_PATHS', [
    'webkernel' => WEBKERNEL_PATH . '/resources/lang',
]);

/**
 * @const string[] WEBKERNEL_MIGRATION_PATHS — Extra migration paths
 */
define('WEBKERNEL_MIGRATION_PATHS', []);

/**
 * @const string[] WEBKERNEL_LIVEWIRE_PATHS — Livewire component namespaces
 */
define('WEBKERNEL_LIVEWIRE_PATHS', [
    'Webkernel\\Livewire' => WEBKERNEL_PATH . '/Livewire',
]);

/**
 * @const string[] WEBKERNEL_COMMAND_PATHS — Artisan command discovery paths
 */
define('WEBKERNEL_COMMAND_PATHS', [
    WEBKERNEL_PATH . '/Console/Commands',
]);

/**
 * @const array WEBKERNEL_BLAZE_PATHS — Blaze optimizer specifications
 */
define('WEBKERNEL_BLAZE_PATHS', [
    [
        'path'    => WEBKERNEL_SUPPORT_PATH . '/views/components',
        'compile' => true,
        'memo'    => false,
        'fold'    => false,
    ],
    [
        'path'    => WEBKERNEL_PATH . '/resources/export-svg',
        'compile' => true,
        'memo'    => false,
        'fold'    => false,
    ],
]);

defined('SVG_COLLECTION_PATHS')
    || define('SVG_COLLECTION_PATHS', [
        WEBKERNEL_SUPPORT_PATH . '/resources/export-svg/custom',
        WEBKERNEL_SUPPORT_PATH . '/resources/export-svg/lucide',
        WEBKERNEL_SUPPORT_PATH . '/resources/export-svg/simple-icons',
    ]);

/**
 * Grab an SVG icon from the Webkernel collections.
 *
 * @param string $filename Name of the SVG file (without extension).
 * @return string|null SVG contents or null if not found.
 */
function grab_webkernel_icon(string $filename): ?string
{
    if (!defined('SVG_COLLECTION_PATHS')) {
        throw new RuntimeException('SVG_COLLECTION_PATHS not defined.');
    }
    foreach (SVG_COLLECTION_PATHS as $path) {
        $fullPath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename . '.svg';
        if (is_file($fullPath)) {
            return file_get_contents($fullPath);
        }
    }
    return null; // Not found
}

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 8: REGISTRY & API ENDPOINTS
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * @const string[] WEBKERNEL_MODULE_REGISTRIES — Known module registries
 */
define('WEBKERNEL_MODULE_REGISTRIES', [
    'webkernelphp.com'     => 'https://webkernelphp.com/api/v1/modules',
    'github.com'           => 'https://api.github.com',
    'gitlab.com'           => 'https://gitlab.com/api/v4',
    'bitbucket.org'        => 'https://api.bitbucket.org/2.0',
    'git.numerimondes.com' => 'https://git.numerimondes.com/api/v1',
]);

/**
 * @const string WEBKERNEL_MARKETPLACE_API — Main marketplace API
 */
define('WEBKERNEL_MARKETPLACE_API', 'https://webkernelphp.com/api/v1');

/**
 * @const string WEBKERNEL_UPDATE_CHECK_URL — Latest release check
 */
define('WEBKERNEL_UPDATE_CHECK_URL', 'https://webkernelphp.com/api/v1/releases/latest');

/**
 * @const string WEBKERNEL_MODULES_API — Module discovery API
 */
define('WEBKERNEL_MODULES_API', 'https://webkernelphp.com/api/v1/modules');

/**
 * @const string WEBKERNEL_PHP_RELEASES_API — PHP release information
 */
define('WEBKERNEL_PHP_RELEASES_API', 'https://www.php.net/releases/active.php');

/**
 * @const int WEBKERNEL_PHP_RELEASES_TTL — Cache TTL for PHP releases (seconds)
 */
define('WEBKERNEL_PHP_RELEASES_TTL', 86400);

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 9: WEBSOCKET & MESSAGING
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * @const string WEBKERNEL_WS_CHANNEL_SYSTEM — System event channel
 */
define('WEBKERNEL_WS_CHANNEL_SYSTEM', 'webkernel.system');

/**
 * @const string WEBKERNEL_WS_CHANNEL_AUDIT — Audit log channel
 */
define('WEBKERNEL_WS_CHANNEL_AUDIT', 'webkernel.audit');

/**
 * @const string WEBKERNEL_WS_CHANNEL_METRICS — Metrics channel
 */
define('WEBKERNEL_WS_CHANNEL_METRICS', 'webkernel.metrics');

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 10: DEPLOYMENT CONTEXTS
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * @const string WEBKERNEL_CONTEXT_MEDICAL — Medical/healthcare deployment
 */
define('WEBKERNEL_CONTEXT_MEDICAL', 'medical');

/**
 * @const string WEBKERNEL_CONTEXT_SURGICAL — Surgical operations
 */
define('WEBKERNEL_CONTEXT_SURGICAL', 'surgical');

/**
 * @const string WEBKERNEL_CONTEXT_GOV — Government/public sector
 */
define('WEBKERNEL_CONTEXT_GOV', 'governmental');

/**
 * @const string WEBKERNEL_CONTEXT_FINANCIAL — Financial services
 */
define('WEBKERNEL_CONTEXT_FINANCIAL', 'financial');

/**
 * @const string WEBKERNEL_CONTEXT_STANDARD — Standard/unrestricted
 */
define('WEBKERNEL_CONTEXT_STANDARD', 'standard');

/**
 * @const int WEBKERNEL_AUDIT_RETENTION_DAYS — Audit log retention policy
 */
define('WEBKERNEL_AUDIT_RETENTION_DAYS', 365);

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 11: INSTALLATION & ROUTING
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * @const string WEBKERNEL_DEPLOYMENT_FILE — Deployment metadata file
 */
define('WEBKERNEL_DEPLOYMENT_FILE', BASE_PATH . '/deployment.php');

/**
 * @const string WEBKERNEL_HTTP_ROOT — HTTP root path prefix
 */
define('WEBKERNEL_HTTP_ROOT', '/');

/**
 * @const string WEBKERNEL_INSTALLER_PATH_PREFIX — Installation wizard path
 */
define('WEBKERNEL_INSTALLER_PATH_PREFIX', 'installer');

/**
 * @const string WEBKERNEL_INSTALLER_URL — Full installer URL
 */
define('WEBKERNEL_INSTALLER_URL', WEBKERNEL_HTTP_ROOT . WEBKERNEL_INSTALLER_PATH_PREFIX);

/**
 * @const string WEBKERNEL_HEALTH_PATH — Health check endpoint
 */
define('WEBKERNEL_HEALTH_PATH', 'up');

/**
 * @const string DEVMODE_FILE — Dev tools configuration file
 */
define('DEVMODE_FILE', BASE_PATH . '/dev-tools.php');

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 12: RUNTIME & THRESHOLDS
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * @const string WEBKERNEL_MIN_PHP_SERIES — Minimum PHP major.minor
 */
define('WEBKERNEL_MIN_PHP_SERIES', '8.2');

/**
 * @const string WEBKERNEL_RECOMMENDED_PHP_SERIES — Recommended PHP series
 */
define('WEBKERNEL_RECOMMENDED_PHP_SERIES', '8.4');

/**
 * @const string WEBKERNEL_MIN_COMPOSER — Minimum Composer version
 */
define('WEBKERNEL_MIN_COMPOSER', '2.5.0');

/**
 * @const string WEBKERNEL_RECOMMENDED_COMPOSER — Recommended Composer version
 */
define('WEBKERNEL_RECOMMENDED_COMPOSER', '2.8.0');

/**
 * @const int WEBKERNEL_MIN_MEMORY_LIMIT_MB — Minimum memory limit
 */
define('WEBKERNEL_MIN_MEMORY_LIMIT_MB', 128);

/**
 * @const int WEBKERNEL_REC_MEMORY_LIMIT_MB — Recommended memory limit
 */
define('WEBKERNEL_REC_MEMORY_LIMIT_MB', 512);

/**
 * @const int WEBKERNEL_MIN_MAX_EXEC_TIME — Minimum execution time (seconds)
 */
define('WEBKERNEL_MIN_MAX_EXEC_TIME', 30);

/**
 * @const int WEBKERNEL_REC_MAX_EXEC_TIME — Recommended execution time (seconds)
 */
define('WEBKERNEL_REC_MAX_EXEC_TIME', 120);

/**
 * @const int WEBKERNEL_MIN_UPLOAD_MAX_MB — Minimum upload limit (MB)
 */
define('WEBKERNEL_MIN_UPLOAD_MAX_MB', 32);

/**
 * @const int WEBKERNEL_MIN_POST_MAX_MB — Minimum POST limit (MB)
 */
define('WEBKERNEL_MIN_POST_MAX_MB', 32);

/**
 * @const int WEBKERNEL_DISK_CRITICAL_BYTES — Critical disk usage threshold
 */
define('WEBKERNEL_DISK_CRITICAL_BYTES', 1_073_741_824);

/**
 * @const int WEBKERNEL_DISK_WARNING_BYTES — Warning disk usage threshold
 */
define('WEBKERNEL_DISK_WARNING_BYTES', 5_368_709_120);

/**
 * @const int WEBKERNEL_SLOW_REQUEST_MS — Slow request threshold (milliseconds)
 */
define('WEBKERNEL_SLOW_REQUEST_MS', 500);

/**
 * @const int WEBKERNEL_SLOW_QUERY_MS — Slow query threshold (milliseconds)
 */
define('WEBKERNEL_SLOW_QUERY_MS', 100);

/**
 * @const int WEBKERNEL_SLOW_JOB_MS — Slow job threshold (milliseconds)
 */
define('WEBKERNEL_SLOW_JOB_MS', 5000);

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 13: EXTENSIONS & SECURITY
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * @const string[] WEBKERNEL_REQUIRED_EXTENSIONS — Mandatory PHP extensions
 */
define('WEBKERNEL_REQUIRED_EXTENSIONS', [
    'openssl', 'pdo', 'mbstring', 'tokenizer', 'xml', 'ctype',
    'json', 'bcmath', 'curl', 'fileinfo', 'gd', 'intl', 'zip', 'sodium',
]);

/**
 * @const string[] WEBKERNEL_CRITICAL_EXTENSIONS — Security-critical extensions
 */
define('WEBKERNEL_CRITICAL_EXTENSIONS', [
    'openssl'  => 'TLS and cryptographic operations',
    'sodium'   => 'Authenticated encryption — mandatory for medical, government, financial deployments',
    'pdo'      => 'Database abstraction layer',
    'mbstring' => 'Multi-byte string handling — mandatory for internationalisation',
    'json'     => 'JSON encoding and decoding',
    'curl'     => 'HTTP client — required for remote integrity verification',
]);

/**
 * @const int WEBKERNEL_SECURITY_SCORE_CRITICAL — Critical security score threshold
 */
define('WEBKERNEL_SECURITY_SCORE_CRITICAL', 40);

/**
 * @const int WEBKERNEL_SECURITY_SCORE_WARNING — Warning security score threshold
 */
define('WEBKERNEL_SECURITY_SCORE_WARNING', 70);

/**
 * @const int WEBKERNEL_SECURITY_SCORE_GOOD — Good security score threshold
 */
define('WEBKERNEL_SECURITY_SCORE_GOOD', 85);

/**
 * @const string[] WEBKERNEL_ENV_MASK_PATTERNS — Sensitive environment variable patterns
 */
define('WEBKERNEL_ENV_MASK_PATTERNS', [
    'key', 'secret', 'password', 'passwd', 'token', 'auth', 'pwd',
    'credential', 'private', 'cert', 'hash', 'salt', 'cipher',
    'connection_string', 'dsn', 'webhook', 'passphrase', 'pin',
]);

/**
 * @const array WEBKERNEL_DANGEROUS_FUNCTIONS — Function risk scoring for static analysis
 */
define('WEBKERNEL_DANGEROUS_FUNCTIONS', [
    'exec'                => ['score' => 100, 'category' => 'RCE',        'description' => 'Execute external program'],
    'shell_exec'          => ['score' => 100, 'category' => 'RCE',        'description' => 'Execute command via shell'],
    'system'              => ['score' => 100, 'category' => 'RCE',        'description' => 'Execute system command and display output'],
    'passthru'            => ['score' => 100, 'category' => 'RCE',        'description' => 'Execute command, pass raw output to client'],
    'popen'               => ['score' => 95,  'category' => 'RCE',        'description' => 'Open process file pointer'],
    'proc_open'           => ['score' => 95,  'category' => 'RCE',        'description' => 'Execute command with configurable stream access'],
    'pcntl_exec'          => ['score' => 100, 'category' => 'RCE',        'description' => 'Execute program replacing current PHP process'],
    'eval'                => ['score' => 100, 'category' => 'RCE',        'description' => 'Evaluate arbitrary PHP string as code'],
    'assert'              => ['score' => 85,  'category' => 'RCE',        'description' => 'Executes code when passed a string argument'],
    'create_function'     => ['score' => 95,  'category' => 'RCE',        'description' => 'Deprecated eval-based code execution vector'],
    'unserialize'         => ['score' => 95,  'category' => 'Deser',      'description' => 'PHP object deserialization — POP chain risk'],
    'FFI::cdef'           => ['score' => 100, 'category' => 'FFI',        'description' => 'Load and define C functions'],
    'FFI::load'           => ['score' => 100, 'category' => 'FFI',        'description' => 'Load C header with lib directive'],
    'FFI::scope'          => ['score' => 90,  'category' => 'FFI',        'description' => 'Access preloaded FFI scope'],
    'ffi_cdef'            => ['score' => 100, 'category' => 'FFI',        'description' => 'FFI procedural alias'],
    'file_put_contents'   => ['score' => 70,  'category' => 'Write',      'description' => 'Write arbitrary data to any accessible file'],
    'unlink'              => ['score' => 75,  'category' => 'Destroy',    'description' => 'Delete files'],
    'rmdir'               => ['score' => 70,  'category' => 'Destroy',    'description' => 'Remove directories'],
    'chmod'               => ['score' => 80,  'category' => 'Priv',       'description' => 'Change file permissions'],
    'chown'               => ['score' => 85,  'category' => 'Priv',       'description' => 'Change file ownership'],
    'symlink'             => ['score' => 75,  'category' => 'LFI',        'description' => 'Create symbolic links'],
    'file_get_contents'   => ['score' => 40,  'category' => 'LFI',        'description' => 'Read local or remote files'],
    'fopen'               => ['score' => 50,  'category' => 'LFI',        'description' => 'Open files or URLs'],
    'readfile'            => ['score' => 60,  'category' => 'LFI',        'description' => 'Output file content directly'],
    'highlight_file'      => ['score' => 70,  'category' => 'Disclosure', 'description' => 'Output PHP source code'],
    'show_source'         => ['score' => 70,  'category' => 'Disclosure', 'description' => 'Alias of highlight_file'],
    'phpinfo'             => ['score' => 75,  'category' => 'Disclosure', 'description' => 'Discloses full server configuration'],
    'var_dump'            => ['score' => 20,  'category' => 'Disclosure', 'description' => 'Debug output — must not exist in production'],
    'debug_backtrace'     => ['score' => 30,  'category' => 'Disclosure', 'description' => 'Exposes call stack'],
    'call_user_func'      => ['score' => 70,  'category' => 'Dynamic',    'description' => 'Dynamic function call'],
    'call_user_func_array'=> ['score' => 70,  'category' => 'Dynamic',    'description' => 'Dynamic function call with array arguments'],
    'extract'             => ['score' => 65,  'category' => 'Injection',  'description' => 'Variable overwrite from array'],
    'parse_str'           => ['score' => 60,  'category' => 'Injection',  'description' => 'Parse query string into variables'],
    'fsockopen'           => ['score' => 65,  'category' => 'Network',    'description' => 'Raw socket connection'],
    'putenv'              => ['score' => 55,  'category' => 'Env',        'description' => 'Modify process environment variables at runtime'],
    'ini_set'             => ['score' => 50,  'category' => 'Config',     'description' => 'Override PHP configuration at runtime'],
    'dl'                  => ['score' => 100, 'category' => 'Extension',  'description' => 'Dynamically load a PHP extension'],
    'base64_decode'       => ['score' => 20,  'category' => 'Obfuscation','description' => 'Commonly used to hide encoded payloads'],
    'md5'                 => ['score' => 30,  'category' => 'Crypto',     'description' => 'Cryptographically broken — do not use for signatures or passwords'],
    'sha1'                => ['score' => 25,  'category' => 'Crypto',     'description' => 'Weak — use SHA-256 or stronger'],
    'move_uploaded_file'  => ['score' => 65,  'category' => 'Upload',     'description' => 'Verify MIME type and extension before calling'],
    'include'             => ['score' => 70,  'category' => 'LFI',        'description' => 'Dynamic include — path traversal risk'],
    'include_once'        => ['score' => 70,  'category' => 'LFI',        'description' => 'Dynamic include_once — path traversal risk'],
    'require'             => ['score' => 70,  'category' => 'LFI',        'description' => 'Dynamic require — path traversal risk'],
    'require_once'        => ['score' => 70,  'category' => 'LFI',        'description' => 'Dynamic require_once — path traversal risk'],
    'set_include_path'    => ['score' => 55,  'category' => 'LFI',        'description' => 'Manipulate PHP include path'],
    'copy'                => ['score' => 60,  'category' => 'Write',      'description' => 'Copy files including from stream wrappers'],
    'rename'              => ['score' => 65,  'category' => 'Write',      'description' => 'Rename or move files'],
    'preg_replace'        => ['score' => 70,  'category' => 'RCE',        'description' => 'Audit for obfuscated patterns'],
    'json_decode'         => ['score' => 10,  'category' => 'Deser',      'description' => 'Always use associative array mode'],
    'socket_create'       => ['score' => 60,  'category' => 'Network',    'description' => 'Raw socket creation — SSRF risk'],
]);

// ═══════════════════════════════════════════════════════════════════════════════
// SECTION 14: PATH HELPER FUNCTIONS (Strictly Typed)
// ═══════════════════════════════════════════════════════════════════════════════

if (!function_exists('pdir_path')) {
    /**
     * Resolve a path relative to PLATFORM_DIR.
     *
     * @param string $subpath Path fragment (e.g., 'config/app.php')
     * @return string Absolute path
     */
    function pdir_path(string $subpath): string
    {
        return PLATFORM_DIR . DIRECTORY_SEPARATOR . ltrim($subpath, DIRECTORY_SEPARATOR);
    }
}

if (!function_exists('platform_config_path')) {
    /**
     * Resolve a path relative to PLATFORM_CONFIG_PATH.
     *
     * @param string $subpath Path fragment
     * @return string Absolute path
     */
    function platform_config_path(string $subpath): string
    {
        return PLATFORM_CONFIG_PATH . DIRECTORY_SEPARATOR . ltrim($subpath, DIRECTORY_SEPARATOR);
    }
}

if (!function_exists('platform_storage_path')) {
    /**
     * Resolve a path relative to PLATFORM_STORAGE_PATH.
     *
     * @param string $subpath Path fragment
     * @return string Absolute path
     */
    function platform_storage_path(string $subpath): string
    {
        return PLATFORM_STORAGE_PATH . DIRECTORY_SEPARATOR . ltrim($subpath, DIRECTORY_SEPARATOR);
    }
}

if (!function_exists('platform_packages_path')) {
    /**
     * Resolve a path relative to PLATFORM_PACKAGES_PATH.
     *
     * @param string $subpath Path fragment
     * @return string Absolute path
     */
    function platform_packages_path(string $subpath): string
    {
        return PLATFORM_PACKAGES_PATH . DIRECTORY_SEPARATOR . ltrim($subpath, DIRECTORY_SEPARATOR);
    }
}

if (!function_exists('webkernel_path')) {
    /**
     * Resolve a path relative to WEBKERNEL_PATH (PHP source tree).
     *
     * @param string $subpath Path fragment
     * @return string Absolute path
     */
    function webkernel_path(string $subpath): string
    {
        return WEBKERNEL_PATH . DIRECTORY_SEPARATOR . ltrim($subpath, DIRECTORY_SEPARATOR);
    }
}

if (!function_exists('webkernel_upperpath')) {
    /**
     * Resolve a path relative to WEBKERNEL_UPPERPATH (package root).
     *
     * @param string $subpath Path fragment
     * @return string Absolute path
     */
    function webkernel_upperpath(string $subpath): string
    {
        return WEBKERNEL_UPPERPATH . DIRECTORY_SEPARATOR . ltrim($subpath, DIRECTORY_SEPARATOR);
    }
}

if (!function_exists('env_template_content')) {
    /**
     * Read environment template file safely.
     * Returns empty string if template not found.
     *
     * @return string Template content or empty string
     */
    function env_template_content(): string
    {
        $path = ENV_PATH_TEMPLATE;
        if ($path === '' || !is_file($path)) {
            return '';
        }
        $content = @file_get_contents($path);
        return $content !== false ? $content : '';
    }
}
