<?php declare(strict_types=1);

/**
 * Platform capability: system_panel
 * Namespace: Webkernel\BackOffice\System
 *
 * Three pillars:
 *   Control   — observability and metrics (Host, Instance, OS, External)
 *   Integrity — security auditing, access control, error pages
 *   Lifecycle — platform core, updater, multitenancy, route handling
 */
return [
    'label'       => 'System Manager',
    'description' => 'Core System Management Aptitudes',
    'version'     => '2.0.0',
    'active'      => true,
    'party'       => 'first',

    'namespace' => 'Webkernel\BackOffice\System',

    'providers' => [
        Webkernel\BackOffice\System\Providers\SystemManagerServiceProvider::class,
        Webkernel\BackOffice\System\Providers\SystemPanelProvider::class,
    ],

    'helpers'       => [],
    'helpers_paths' => [
        ['path' => 'helpers', 'depth' => 0],
    ],

    'route_groups' => [],
    'route_paths'  => ['Routes'],

    'config_paths' => ['Config'],

    'view_namespaces' => [
        'webkernel-system' => 'Resources/Views',
    ],

    'lang_paths' => [
        'webkernel-system-manager' => 'Lang',
    ],

    'migration_paths' => ['database/migrations'],
    'seeder_paths'    => [],

    'command_paths' => ['Console'],

    'livewire_paths' => [],

    'filament_paths' => [
        'Presentation',
        'Presentation/Widgets',
    ],

    'asset_paths' => [],

    'blade_to_optimize_paths' => [
        ['path' => 'Resources/Views/Blaze', 'compile' => false, 'fold' => false, 'memo' => false, 'safe' => [], 'unsafe' => []],
    ],

    'depends' => [],

    'compatibility' => [
        'php'       => '>=8.4',
        'laravel'   => '>=12.0',
        'webkernel' => '>=2.0.0',
    ],

    'created_at' => '2026-02-28T00:00:00+00:00',
];
