<?php declare(strict_types=1);
namespace Webkernel\Arcanes\Scaffold;

use Webkernel\Arcanes\Matrix\ArtifactMatrix;

/**
 * Generates every file written by the Scaffolder.
 * All section and field knowledge comes from ArtifactMatrix.
 */
final class StubRenderer
{
    // ── Manifest ──────────────────────────────────────────────────────────────

    public static function manifest(ScaffoldParams $p, string $providerClass, array $resolved): string
    {
        $now = now()->toIso8601String();

        $entry = array_merge(ArtifactMatrix::normalize([]), [
            'label'       => $p->label,
            'description' => $p->description,
            'version'     => $p->version,
            'active'      => true,
            'namespace'   => $p->namespace,
            'providers'   => [$providerClass . '::class'],
            'depends'     => [],
            'compatibility' => [
                'php'       => '>=' . $p->phpVersion,
                'laravel'   => '>=' . $p->laravelVersion,
                'webkernel' => '>=1.0.0',
            ],
            'created_at' => $now,
        ], $p->isModule() ? [
            'id'       => $p->id,
            'registry' => $p->registry,
            'vendor'   => $p->vendor,
            'slug'     => $p->slug,
            'party'    => $p->party,
            'author'   => ['name' => $p->authorName, 'email' => $p->authorEmail, 'url' => $p->authorUrl],
            'license'  => $p->license,
            'certification' => ['certified_at' => null, 'certified_hash' => null],
        ] : [], $resolved);

        $header = $p->isAptitude()
            ? "<?php declare(strict_types=1);\n\n/**\n * Aptitude: {$p->slug}\n * Canonical id: {$p->id}\n * Do not declare: id, registry, vendor, slug, _root, _type (injected at discovery)\n */\n\n"
            : "<?php declare(strict_types=1);\n\n";

        $lines = ["return [\n"];
        foreach (ArtifactMatrix::sections($p->type) as $sectionKey => $keys) {
            $lines[] = self::sectionHeader($sectionKey);
            foreach ($keys as $key) {
                $lines[] = self::renderKey($key, $entry[$key] ?? ArtifactMatrix::defaultFor($key));
            }
            $lines[] = '';
        }
        $lines[] = "];\n";

        return $header . implode("\n", $lines);
    }

    // ── Non-manifest files ────────────────────────────────────────────────────

    public static function serviceProvider(string $namespace, string $class): string
    {
        $ns = rtrim($namespace, '\\');
        return <<<PHP
        <?php declare(strict_types=1);
        namespace {$ns}\\Providers;

        use Illuminate\Support\ServiceProvider;

        class {$class}ServiceProvider extends ServiceProvider
        {
            public function register(): void {}
            public function boot(): void {}
        }
        PHP;
    }

    public static function routeFile(string $namespace, string $group): string
    {
        $ns = rtrim($namespace, '\\');
        return "<?php declare(strict_types=1);\n\nuse Illuminate\\Support\\Facades\\Route;\n\n// {$group} routes — {$ns}\n";
    }

    public static function configFile(string $key, string $label): string
    {
        return "<?php declare(strict_types=1);\n\n// {$label}\n// Access via: config('{$key}.key')\nreturn [\n];\n";
    }

    public static function langMessages(): string
    {
        return "<?php\n\nreturn [\n\n];\n";
    }

    public static function composerJson(ScaffoldParams $p): string
    {
        $ns     = rtrim($p->namespace, '\\') . '\\';
        $author = array_filter(['name' => $p->authorName, 'email' => $p->authorEmail, 'homepage' => $p->authorUrl ?: null]);
        return json_encode([
            'name'        => strtolower($p->vendor) . '/' . $p->slug,
            'description' => $p->description,
            'type'        => 'webkernel-module',
            'license'     => $p->license,
            'version'     => $p->version,
            'authors'     => [$author],
            'require'     => ['php' => '>=' . $p->phpVersion, 'laravel/framework' => '>=' . $p->laravelVersion],
            'autoload'    => ['psr-4' => [$ns => 'src/']],
            'extra'       => ['webkernel' => ['module' => true, 'registry' => $p->registry]],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }

    public static function licenseFile(ScaffoldParams $p): string
    {
        return "WEBKERNEL MODULE LICENSE — PROPRIETARY\nCopyright (c) " . now()->year . " {$p->authorName}\nSee: https://webkernelphp.com/license\n";
    }

    public static function readme(ScaffoldParams $p): string
    {
        $type = $p->isAptitude() ? "Internal aptitude\nLives at `bootstrap/webkernel/aptitudes/{$p->folderName}/`." : "Module at `modules/{$p->registry}/{$p->vendor}/{$p->slug}/`.";
        return "# {$p->label}\n\n{$p->description}\n\n| Field | Value |\n|---|---|\n| ID | `{$p->id}` |\n| Namespace | `{$p->namespace}` |\n\n## Requirements\n- PHP >= {$p->phpVersion}\n- Laravel >= {$p->laravelVersion}\n\n{$type}\n";
    }

    // ── PHP literal rendering ─────────────────────────────────────────────────

    public static function renderKey(string $key, mixed $value, int $depth = 1): string
    {
        $pad = str_repeat('    ', $depth);

        if ($key === 'providers') {
            return self::renderProviders((array) $value, $depth);
        }
        if ($key === 'helpers_paths') {
            return $pad . "'{$key}' => " . self::renderHelpersPaths((array) $value, $depth) . ',';
        }
        if ($key === 'route_groups') {
            return $pad . "'{$key}' => " . self::renderRouteGroups((array) $value, $depth) . ',';
        }
        if (is_array($value)) {
            if (array_is_list($value) || empty($value)) {
                return $pad . "'{$key}' => " . self::renderList($value, $depth) . ',';
            }
            $inner = array_filter($value, 'is_array');
            return $pad . "'{$key}' => " . (empty($inner) ? self::renderAssoc($value, $depth) : self::renderMap($value, $depth)) . ',';
        }
        if (is_bool($value))  { return $pad . "'{$key}' => " . ($value ? 'true' : 'false') . ','; }
        if ($value === null)  { return $pad . "'{$key}' => null,"; }
        return $pad . "'{$key}' => '{$value}',";
    }

    // ── Private renderers ─────────────────────────────────────────────────────

    private static function sectionHeader(string $key): string
    {
        $dashes = str_repeat('-', max(0, 74 - strlen($key)));
        return "    /*-- {$key} {$dashes}*/";
    }

    private static function renderProviders(array $providers, int $depth): string
    {
        $pad   = str_repeat('    ', $depth);
        $inner = str_repeat('    ', $depth + 1);
        if (empty($providers)) {
            return $pad . "'providers' => [],";
        }
        $lines = [$pad . "'providers' => ["];
        foreach ($providers as $p) {
            $lines[] = $inner . (str_ends_with((string) $p, '::class') ? $p : "'{$p}'") . ',';
        }
        $lines[] = $pad . '],';
        return implode("\n", $lines);
    }

    private static function renderHelpersPaths(array $specs, int $depth): string
    {
        if (empty($specs)) {
            return '[]';
        }
        $pad   = str_repeat('    ', $depth);
        $inner = str_repeat('    ', $depth + 1);
        $lines = array_map(static fn ($s) => $inner . "['path' => '{$s['path']}', 'depth' => {$s['depth']}],", $specs);
        return "[\n" . implode("\n", $lines) . "\n{$pad}    ]";
    }

    private static function renderRouteGroups(array $groups, int $depth): string
    {
        if (empty($groups)) {
            return '[]';
        }
        $pad   = str_repeat('    ', $depth);
        $inner = str_repeat('    ', $depth + 1);
        $lines = [];
        foreach ($groups as $group => $files) {
            $list    = implode(', ', array_map(static fn ($f) => "'{$f}'", (array) $files));
            $lines[] = $inner . "'{$group}' => [{$list}],";
        }
        return "[\n" . implode("\n", $lines) . "\n{$pad}    ]";
    }

    private static function renderAssoc(array $data, int $depth): string
    {
        $pad    = str_repeat('    ', $depth);
        $inner  = str_repeat('    ', $depth + 1);
        $maxLen = empty($data) ? 0 : max(array_map('strlen', array_keys($data)));
        $lines  = [];
        foreach ($data as $k => $v) {
            $rendered = match (true) {
                is_null($v)   => 'null',
                is_bool($v)   => ($v ? 'true' : 'false'),
                is_string($v) => "'{$v}'",
                default       => (string) $v,
            };
            $lines[] = $inner . str_pad("'{$k}'", $maxLen + 2) . " => {$rendered},";
        }
        return "[\n" . implode("\n", $lines) . "\n{$pad}    ]";
    }

    private static function renderMap(array $data, int $depth): string
    {
        if (empty($data)) {
            return '[]';
        }
        $pad    = str_repeat('    ', $depth);
        $inner  = str_repeat('    ', $depth + 1);
        $maxLen = max(array_map('strlen', array_keys($data)));
        $lines  = array_map(
            static fn ($k, $v) => $inner . str_pad("'{$k}'", $maxLen + 2) . " => '{$v}',",
            array_keys($data), array_values($data)
        );
        return "[\n" . implode("\n", $lines) . "\n{$pad}    ]";
    }

    private static function renderList(array $data, int $depth): string
    {
        if (empty($data)) {
            return '[]';
        }
        $pad   = str_repeat('    ', $depth);
        $inner = str_repeat('    ', $depth + 1);
        $lines = array_map(static fn ($v) => is_array($v) ? $inner . self::renderInlineArray($v) . ',' : $inner . "'{$v}',", $data);
        return "[\n" . implode("\n", $lines) . "\n{$pad}    ]";
    }

    private static function renderInlineArray(array $data): string
    {
        $pairs = [];
        foreach ($data as $k => $v) {
            $val    = match (true) {
                is_null($v)   => 'null',
                is_bool($v)   => ($v ? 'true' : 'false'),
                is_array($v)  => '[]',
                is_string($v) => "'{$v}'",
                default       => (string) $v,
            };
            $pairs[] = "'{$k}' => {$val}";
        }
        return '[' . implode(', ', $pairs) . ']';
    }
}
