<?php declare(strict_types=1);

/**
 * Webkernel IDE Helper Generator
 *
 * Generates IDE helper files for Webkernel functions, classes, and helpers.
 * Runnable as: php bootstrap/webkernel/support/boot/_dev_tools/_ide_helper_generator.php [output_file]
 *
 * @usage php bootstrap/webkernel/support/boot/_dev_tools/_ide_helper_generator.php
 * @usage php bootstrap/webkernel/support/boot/_dev_tools/_ide_helper_generator.php /custom/path/_ide_helper_custom.php
 */


// === Constants ================================================================

const DEFAULT_OUTPUT_FILE = '_ide_helper_webkernel.php';


// === Bootstrap ================================================================

if (PHP_VERSION_ID < 80400) {
    fwrite(STDERR, "Error: Webkernel requires PHP 8.4+\n");
    exit(1);
}

// Locate project root by finding vendor/autoload.php (works from any call context)
if (!defined('BASE_PATH')) {
    $dir = __DIR__;
    while ($dir !== dirname($dir)) {
        if (file_exists($dir . '/vendor/autoload.php')) {
            define('BASE_PATH', $dir);
            break;
        }
        $dir = dirname($dir);
    }
    if (!defined('BASE_PATH')) {
        fwrite(STDERR, "Error: Could not locate vendor/autoload.php\n");
        exit(1);
    }
}

// Load Composer autoloader ONLY (no full bootstrap - prevents WebApp class loading)
require_once BASE_PATH . '/vendor/autoload.php';

// === Helpers ==================================================================

/**
 * Generate docblock for a function.
 *
 * @param string $functionName
 * @param string $signature Return type and params
 * @param string $description Optional description
 *
 * @return string
 */
function generate_function_docblock(
    string $functionName,
    string $signature,
    string $description = ''
): string {
    $doc = "/**\n";

    if ($description) {
        $doc .= " * " . $description . "\n";
        $doc .= " *\n";
    }

    $doc .= " * @generated IDE helper (do not edit manually)\n";
    $doc .= " */\n";

    return $doc;
}

/**
 * Escape PHP code string safely.
 *
 * @param string $code
 *
 * @return string
 */
function escape_php(string $code): string {
    return addslashes($code);
}

/**
 * Generate a complete IDE helper file.
 *
 * @param string $outputPath Full path to output file
 * @param array<int, array<string, string>> $entries Array of helper entries
 *
 * @return bool True on success, false otherwise
 */
function generate_ide_helper_file(string $outputPath, array $entries): bool {
    $dir = dirname($outputPath);

    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            fwrite(STDERR, "Error: Cannot create directory: $dir\n");
            return false;
        }
    }

    $content = "<?php declare(strict_types=1);\n\n";
    $content .= "/**\n";
    $content .= " * Webkernel IDE Helper File\n";
    $content .= " *\n";
    $content .= " * This file is auto-generated and should not be manually edited.\n";
    $content .= " * Regenerate with: php bootstrap/webkernel/support/boot/_dev_tools/_ide_helper_generator.php\n";
    $content .= " *\n";
    $content .= " * @generated " . date('Y-m-d H:i:s') . "\n";
    $content .= " */\n\n";

    foreach ($entries as $entry) {
        if (!isset($entry['name'], $entry['signature'])) {
            continue;
        }

        $docblock = generate_function_docblock(
            $entry['name'],
            $entry['signature'],
            $entry['description'] ?? ''
        );

        $returnStub = 'null';
        if (preg_match('/:\s*(\S+)\s*$/', $entry['signature'], $m)) {
            $returnStub = match ($m[1]) {
                'string'       => "''",
                'int'          => '0',
                'float'        => '0.0',
                'bool'         => 'false',
                'array'        => '[]',
                'void'         => '',
                'never'        => '',
                'object'       => 'new \stdClass()',
                default        => 'null',
            };
        }

        $body = $returnStub !== '' ? "return $returnStub;" : '';
        $name = $entry['name'];
        $content .= $docblock;
        $content .= "if (!function_exists('$name')) {\n";
        $content .= "    " . $entry['signature'] . " { $body }\n";
        $content .= "}\n\n";
    }

    if (!file_put_contents($outputPath, $content)) {
        fwrite(STDERR, "Error: Cannot write to file: $outputPath\n");
        return false;
    }

    printf("✓ Generated: %s (%d entries)\n", $outputPath, count($entries));

    return true;
}

/**
 * Load and parse helper functions from a PHP file.
 * Expects file to contain function definitions or function-like declarations.
 *
 * @param string $filePath
 *
 * @return array<int, array<string, string>>
 */
function load_helpers_from_file(string $filePath): array {
    if (!file_exists($filePath)) {
        fwrite(STDERR, "Warning: Helper file not found: $filePath\n");
        return [];
    }

    $entries = [];
    $content = file_get_contents($filePath);

    if ($content === false) {
        fwrite(STDERR, "Warning: Cannot read helper file: $filePath\n");
        return [];
    }

    // Simple regex pattern to find function definitions in docblock comments or function() declarations
    // This is a fallback; ideally helpers define themselves in structured format
    if (preg_match_all(
        '/\/\*\*.*?function\s+(\w+)\s*\((.*?)\)\s*:\s*(\w+)/s',
        $content,
        $matches
    )) {
        for ($i = 0; $i < count($matches[1]); ++$i) {
            $entries[] = [
                'name'        => $matches[1][$i],
                'signature'   => sprintf(
                    'function %s(%s): %s',
                    $matches[1][$i],
                    $matches[2][$i] ?? '',
                    $matches[3][$i] ?? 'mixed'
                ),
                'description' => 'Webkernel helper function',
            ];
        }
    }

    return $entries;
}

/**
 * Core Webkernel helper functions registry.
 * Add all Webkernel global helpers here with proper signatures.
 *
 * @return array<int, array<string, string>>
 */
function get_webkernel_helpers(): array {
    return [
        [
            'name'        => 'webkernelBrandingUrl',
            'signature'   => 'function webkernelBrandingUrl(string $path = ""): string',
            'description' => 'Get Webkernel branding asset URL',
        ],
        [
            'name'        => 'webkernelVersion',
            'signature'   => 'function webkernelVersion(): string',
            'description' => 'Get Webkernel version string',
        ],
        [
            'name'        => 'webkernelSemver',
            'signature'   => 'function webkernelSemver(): string',
            'description' => 'Get Webkernel semantic version',
        ],
        [
            'name'        => 'webkernelBuild',
            'signature'   => 'function webkernelBuild(): int',
            'description' => 'Get Webkernel build number',
        ],
        [
            'name'        => 'webkernelBasePath',
            'signature'   => 'function webkernelBasePath(string $path = ""): string',
            'description' => 'Get Webkernel base path',
        ],
        [
            'name'        => 'webkernelPath',
            'signature'   => 'function webkernelPath(string $path = ""): string',
            'description' => 'Get Webkernel installation path',
        ],
        [
            'name'        => 'webkernelSupport',
            'signature'   => 'function webkernelSupport(string $path = ""): string',
            'description' => 'Get Webkernel support directory path',
        ],
        [
            'name'        => 'webkernelArcanes',
            'signature'   => 'function webkernelArcanes(): object',
            'description' => 'Get Webkernel Arcanes subsystem instance',
        ],
        [
            'name'        => 'webkernelEmergencyPage',
            'signature'   => 'function webkernelEmergencyPage(int $code = 500, string $message = ""): void',
            'description' => 'Render Webkernel emergency error page',
        ],
        [
            'name'        => 'webkernelRouter',
            'signature'   => 'function webkernelRouter(): object',
            'description' => 'Get Webkernel router instance',
        ],
    ];
}

/**
 * Merge multiple helper arrays.
 *
 * @param array<int, array<int, array<string, string>>> $helperArrays
 *
 * @return array<int, array<string, string>>
 */
function merge_helpers(array $helperArrays): array {
    $merged = [];
    $seen = [];

    foreach ($helperArrays as $helpers) {
        foreach ($helpers as $helper) {
            $name = $helper['name'] ?? '';
            if ($name && !isset($seen[$name])) {
                $merged[] = $helper;
                $seen[$name] = true;
            }
        }
    }

    return $merged;
}

// === Main =====================================================================

/** @var int $exitCode */
$exitCode = 0;

// Get output path from CLI args or use default
$outputPath = isset($argv[1]) ? trim($argv[1]) : DEFAULT_OUTPUT_FILE;

// If relative path, make it relative to project root (BASE_PATH)
if (!str_starts_with($outputPath, '/')) {
    $outputPath = BASE_PATH . '/' . $outputPath;
}

printf("Webkernel IDE Helper Generator\n");
printf("Output: %s\n", $outputPath);
printf("---\n");

// Collect all helpers
$allHelpers = [
    get_webkernel_helpers(),
];

// Optionally load additional helper files if provided
if (isset($argv[2])) {
    for ($i = 2; $i < count($argv); ++$i) {
        $customFile = $argv[$i];
        if (!str_starts_with($customFile, '/')) {
            $customFile = BASE_PATH . '/' . $customFile;
        }

        if (is_file($customFile)) {
            printf("Loading: %s\n", $customFile);
            $allHelpers[] = load_helpers_from_file($customFile);
        } else {
            fwrite(STDERR, "Warning: File not found: $customFile\n");
        }
    }
}

// Merge and deduplicate
$helpers = merge_helpers($allHelpers);

// Generate file
if (generate_ide_helper_file($outputPath, $helpers)) {
    printf("Success!\n");
} else {
    $exitCode = 1;
}

exit($exitCode);
