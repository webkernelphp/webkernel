<?php declare(strict_types=1);

/**
 * Platform capability: sys_biz_panel
 * Namespace: Webkernel\BackOffice\Businesses
 *
 * Responsibilities:
 *   Businesses — multi-tenant business management, tenant switching, business settings
 */
return [
    'label'       => 'Business Panel',
    'description' => 'Multi-tenant business management back-office',
    'version'     => '2.0.0',
    'active'      => true,
    'party'       => 'first',

    'namespace' => 'Webkernel\BackOffice\Businesses',

    'providers' => [],

    'helpers'       => [],
    'helpers_paths' => [],

    'route_groups' => [],
    'route_paths'  => [],

    'config_paths' => [],

    'view_namespaces' => [
        'webkernel-biz' => 'Resources/Views',
    ],

    'lang_paths' => [],

    'migration_paths' => [],
    'seeder_paths'    => [],

    'command_paths' => [],

    'livewire_paths' => [],

    'filament_paths' => [
        'Presentation',
        'Presentation/Widgets',
    ],

    'asset_paths' => [],

    'blade_to_optimize_paths' => [],

    'depends' => [],

    'compatibility' => [
        'php'       => '>=8.4',
        'laravel'   => '>=12.0',
        'webkernel' => '>=2.0.0',
    ],

    'created_at' => '2026-04-22T00:00:00+00:00',
];
