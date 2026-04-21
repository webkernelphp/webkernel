<?php declare(strict_types=1);
namespace Webkernel\Arcanes\Matrix;

/**
 * Single source of truth for the entire artifact system.
 *
 * Everything derives from here: slots, presets, sections, entry defaults,
 * cache payload, validation rules, blaze specs.
 *
 * Extension rules:
 *   Add a slot   -- one entry in slots()
 *   Add a preset -- one entry in PRESETS
 *   Add a field  -- one entry in ENTRY_BASE
 *
 * Required keys per artifact kind are declared in
 * support/constants/arcanes.php (WEBKERNEL_ARTIFACT_REQUIRED_KEYS).
 * ArtifactMatrix::required() reads from that constant at runtime so that
 * adding a new kind requires zero changes here.
 */
final class ArtifactMatrix
{
    public const CACHE_PROTOCOL_VERSION = 2;

    // -- Cache payload --------------------------------------------------------
    private const PAYLOAD_BASE = [
        'wk_version'      => self::CACHE_PROTOCOL_VERSION,
        'generated_at'    => '',
        'fingerprint'     => '',
        'rebuilt_because' => '',
        'total'           => 0,
        'active'          => 0,
        'psr4_map'        => [],
        'entries'         => [],
    ];

    // -- Entry defaults -------------------------------------------------------
    private const ENTRY_BASE = [
        'id' => '', 'label' => '', 'description' => '',
        'version' => '', 'active' => false,
        'registry' => '', 'vendor' => '', 'slug' => '', 'party' => 'third',
        'namespace' => '',
        'author'  => ['name' => '', 'email' => '', 'url' => ''],
        'license' => 'proprietary',
        'providers'               => [],
        'helpers'                 => [],
        'helpers_paths'           => [],
        'route_groups'            => [],
        'route_paths'             => [],
        'config_paths'            => [],
        'view_namespaces'         => [],
        'lang_paths'              => [],
        'migration_paths'         => [],
        'seeder_paths'            => [],
        'command_paths'           => [],
        'livewire_paths'          => [],
        'filament_paths'          => [],
        'asset_paths'             => [],
        'blade_to_optimize_paths' => [],
        'depends'                 => [],
        'compatibility' => ['php' => '>=8.4', 'laravel' => '>=12.0', 'webkernel' => '>=1.0.0'],
        'certification' => ['certified_at' => null, 'certified_hash' => null],
        'created_at'  => '',
        '_root'       => '', '_type' => 'module',
        '_registry'   => '', '_vendor' => '', '_slug' => '',
    ];

    // -- Fallback required keys (used only when constant is absent) -----------
    private const REQUIRED_FALLBACK = [
        'module'   => ['id', 'namespace', 'version', 'vendor', 'slug', 'party', 'active', 'registry'],
        'platform' => ['label', 'version', 'active'],
    ];

    // -- Slot registry --------------------------------------------------------
    // slot(label, manifestKey, value, dirs, gitkeep)
    private static function slot(string $label, string $key, mixed $value, array $dirs = [], array $gitkeep = []): array
    {
        return ['label' => $label, 'manifestKey' => $key, 'value' => $value, 'dirs' => $dirs, 'gitkeep' => $gitkeep];
    }

    public static function slots(): array
    {
        return [
            'helpers'    => self::slot('Create helpers/?',            'helpers_paths',           [['path' => 'helpers', 'depth' => 0]], ['helpers'],          ['helpers']),
            'views'      => self::slot('Create resources/views?',     'view_namespaces',          ['__HANDLE__' => 'resources/views'],   ['resources/views'],  ['resources/views']),
            'lang'       => self::slot('Create lang/?',               'lang_paths',               ['__HANDLE__' => 'lang'],              ['lang', 'lang/en']),
            'config'     => self::slot('Create config/?',             'config_paths',             ['config'],                            ['config']),
            'migrations' => self::slot('Create database/migrations?', 'migration_paths',          ['database/migrations'],               ['database/migrations'],  ['database/migrations']),
            'seeders'    => self::slot('Create database/seeders?',    'seeder_paths',             ['database/seeders'],                  ['database/seeders'],     ['database/seeders']),
            'console'    => self::slot('Create src/Console?',         'command_paths',            ['src/Console'],                       ['src/Console'],          ['src/Console']),
            'livewire'   => self::slot('Create src/Livewire?',        'livewire_paths',           ['src/Livewire'],                      ['src/Livewire'],         ['src/Livewire']),
            'filament'   => self::slot('Create src/Filament?',        'filament_paths',           ['src/Filament'],                      ['src/Filament/Pages', 'src/Filament/Resources', 'src/Filament/Widgets']),
            'assets'     => self::slot('Create resources/assets?',    'asset_paths',              ['resources/assets'],                  ['resources/assets/js', 'resources/assets/css']),
            'blaze'      => self::slot('Enable Blaze optimization?',  'blade_to_optimize_paths',  [['path' => 'resources/views', 'compile' => true, 'fold' => false, 'memo' => false, 'safe' => [], 'unsafe' => []]]),
            'routes_web' => self::slot('Create web routes?',          'route_groups',             ['web' => ['routes/web.php']],         ['routes']),
            'routes_api' => self::slot('Create api routes?',          'route_groups',             ['api' => ['routes/api.php']],         ['routes']),
        ];
    }

    // -- Presets --------------------------------------------------------------
    private const DEFAULT_SLOTS = [
        'routes_web', 'routes_api', 'config', 'views', 'lang',
        'migrations', 'seeders', 'console', 'livewire', 'filament', 'assets', 'blaze',
    ];

    private const PRESETS = [
        'classic' => [
            'label' => 'Classic -- standard Laravel layout',
            'extra' => [
                'src/Models', 'src/Services', 'src/Http/Controllers',
                'src/Http/Requests', 'src/Http/Middleware', 'src/Providers',
                'src/Events', 'src/Listeners', 'src/Jobs', 'src/Policies',
                'src/View/Components', 'src/tests/Unit', 'src/tests/Feature',
            ],
        ],
        'custom' => [
            'label' => 'Custom -- define everything manually',
            'extra' => [],
            'slots' => [],
        ],
    ];

    public static function presets(): array
    {
        $out = [];
        foreach (self::PRESETS as $key => $preset) {
            $out[$key] = [
                'label'     => $preset['label'],
                'slots'     => $preset['slots'] ?? self::DEFAULT_SLOTS,
                'extraDirs' => $preset['extra'],
            ];
        }
        return $out;
    }

    public static function preset(string $key): array
    {
        return self::presets()[$key]
            ?? throw new \InvalidArgumentException("Unknown preset [{$key}]");
    }

    // -- Manifest sections ----------------------------------------------------
    private const SECTIONS_COMMON = [
        'IDENTITY'      => ['label', 'description', 'version', 'active'],
        'NAMESPACE'     => ['namespace'],
        'PROVIDERS'     => ['providers'],
        'HELPERS'       => ['helpers', 'helpers_paths'],
        'ROUTES'        => ['route_groups', 'route_paths'],
        'CONFIG'        => ['config_paths'],
        'VIEWS'         => ['view_namespaces'],
        'TRANSLATIONS'  => ['lang_paths'],
        'MIGRATIONS'    => ['migration_paths'],
        'SEEDERS'       => ['seeder_paths'],
        'COMMANDS'      => ['command_paths'],
        'LIVEWIRE'      => ['livewire_paths'],
        'FILAMENT'      => ['filament_paths'],
        'ASSETS'        => ['asset_paths'],
        'BLAZE'         => ['blade_to_optimize_paths'],
        'DEPENDENCIES'  => ['depends'],
        'COMPATIBILITY' => ['compatibility'],
        'TIMESTAMP'     => ['created_at'],
    ];

    public static function sections(string $type): array
    {
        $base = self::SECTIONS_COMMON;
        if ($type === 'platform') {
            return $base;
        }
        return ['IDENTITY' => ['id', 'label', 'description', 'version', 'active']]
            + ['NAMESPACE' => ['namespace']]
            + ['REGISTRY'  => ['registry', 'vendor', 'slug']]
            + ['PARTY'     => ['party']]
            + array_slice($base, 2) // from PROVIDERS onward
            + ['AUTHOR'        => ['author']]
            + ['LICENSE'       => ['license']]
            + ['CERTIFICATION' => ['certification']];
    }

    // -- Slot resolution ------------------------------------------------------
    public static function resolveSlotValue(string $name, string $handle): mixed
    {
        $value = self::slots()[$name]['value'] ?? null;
        if (is_array($value) && array_key_exists('__HANDLE__', $value)) {
            return [$handle => $value['__HANDLE__']];
        }
        return $value;
    }

    public static function mergeRouteGroups(array $existing, mixed $incoming): array
    {
        if (!is_array($incoming)) {
            return $existing;
        }
        foreach ($incoming as $group => $files) {
            $existing[$group] = array_merge($existing[$group] ?? [], (array) $files);
        }
        return $existing;
    }

    // -- Accessors ------------------------------------------------------------
    public static function normalize(array $entry): array
    {
        return array_merge(self::ENTRY_BASE, $entry);
    }

    public static function buildPayload(array $overrides = []): array
    {
        return array_merge(self::PAYLOAD_BASE, $overrides);
    }

    /**
     * Return required manifest keys for a given artifact kind.
     *
     * Reads WEBKERNEL_ARTIFACT_REQUIRED_KEYS first (declared in
     * support/constants/arcanes.php).  Falls back to the internal
     * REQUIRED_FALLBACK constant so the class stays self-contained
     * even when the constant file has not been loaded.
     *
     * Unknown kinds fall back to the 'module' rule set.
     */
    public static function required(string $type): array
    {
        $source = defined('WEBKERNEL_ARTIFACT_REQUIRED_KEYS')
            ? WEBKERNEL_ARTIFACT_REQUIRED_KEYS
            : self::REQUIRED_FALLBACK;

        return $source[$type] ?? $source['module'] ?? [];
    }

    public static function entryGet(array $entry, string $key, mixed $fallback = null): mixed
    {
        return $entry[$key] ?? self::ENTRY_BASE[$key] ?? $fallback;
    }

    public static function defaultFor(string $key): mixed
    {
        return self::ENTRY_BASE[$key] ?? null;
    }

    public static function blazeSpecs(array $entry): array
    {
        $raw = $entry['blade_to_optimize_paths'] ?? [];
        if (!is_array($raw)) {
            return [];
        }

        return array_values(array_map(static fn (mixed $spec) => [
            'path'    => (string) ($spec['path']    ?? ''),
            'compile' => (bool)   ($spec['compile'] ?? true),
            'fold'    => (bool)   ($spec['fold']    ?? false),
            'memo'    => (bool)   ($spec['memo']    ?? false),
            'safe'    => (array)  ($spec['safe']    ?? []),
            'unsafe'  => (array)  ($spec['unsafe']  ?? []),
        ], is_array($raw) ? $raw : []));
    }
}
