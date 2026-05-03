<?php declare(strict_types=1);
/** @var array<string> $args */

if (PHP_VERSION_ID < 80400) {
    fwrite(STDERR, '[ide-helper-generator] PHP 8.4+ required.' . PHP_EOL);
    return;
}

$outputPath = $args[0] ?? '_ide_helper_webkernel.php';
if (!str_starts_with($outputPath, '/')) $outputPath = BASE_PATH . '/' . $outputPath;

$helpers = [
    ['webkernelBrandingUrl',  'function webkernelBrandingUrl(string $path = ""): string',                      'Get Webkernel branding asset URL'],
    ['webkernelVersion',      'function webkernelVersion(): string',                                            'Get Webkernel version string'],
    ['webkernelSemver',       'function webkernelSemver(): string',                                             'Get Webkernel semantic version'],
    ['webkernelBuild',        'function webkernelBuild(): int',                                                 'Get Webkernel build number'],
    ['webkernelBasePath',     'function webkernelBasePath(string $path = ""): string',                          'Get Webkernel base path'],
    ['webkernelPath',         'function webkernelPath(string $path = ""): string',                              'Get Webkernel installation path'],
    ['webkernelSupport',      'function webkernelSupport(string $path = ""): string',                           'Get Webkernel support directory path'],
    ['webkernelArcanes',      'function webkernelArcanes(): object',                                            'Get Webkernel Arcanes subsystem instance'],
    ['webkernelEmergencyPage','function webkernelEmergencyPage(int $code = 500, string $message = ""): void',   'Render Webkernel emergency error page'],
    ['webkernelRouter',       'function webkernelRouter(): object',                                             'Get Webkernel router instance'],
];

// Load extra helper files passed as additional args
for ($i = 1; $i < count($args); $i++) {
    $f = str_starts_with($args[$i], '/') ? $args[$i] : BASE_PATH . '/' . $args[$i];
    if (!is_file($f)) { fwrite(STDERR, '[ide-helper-generator] Not found: ' . $f . PHP_EOL); continue; }
    $src = file_get_contents($f);
    if (preg_match_all('/function\s+(\w+)\s*\((.*?)\)\s*:\s*(\w+)/s', $src, $m)) {
        for ($j = 0; $j < count($m[1]); $j++) {
            $helpers[] = [$m[1][$j], 'function ' . $m[1][$j] . '(' . $m[2][$j] . '): ' . $m[3][$j], 'Webkernel helper'];
        }
    }
}

// Deduplicate by name
$seen = $out = [];
foreach ($helpers as [$name, $sig, $desc]) {
    if (isset($seen[$name])) continue;
    $seen[$name] = true;
    $stub = match (true) {
        str_ends_with($sig, ': string') => "return '';",
        str_ends_with($sig, ': int')    => 'return 0;',
        str_ends_with($sig, ': float')  => 'return 0.0;',
        str_ends_with($sig, ': bool')   => 'return false;',
        str_ends_with($sig, ': array')  => 'return [];',
        str_ends_with($sig, ': void'),
        str_ends_with($sig, ': never')  => '',
        str_ends_with($sig, ': object') => 'return new \stdClass();',
        default                         => 'return null;',
    };
    $out[] = "/** @generated {$desc} */\nif (!function_exists('{$name}')) {\n    {$sig} { {$stub} }\n}\n";
}

$content = "<?php declare(strict_types=1);\n/** @generated " . date('Y-m-d H:i:s') . " - do not edit */\n\n" . implode("\n", $out);

$dir = dirname($outputPath);
is_dir($dir) || mkdir($dir, 0o755, true);
file_put_contents($outputPath, $content);
echo '[ide-helper-generator] ' . count($out) . ' entries -> ' . $outputPath . PHP_EOL;
