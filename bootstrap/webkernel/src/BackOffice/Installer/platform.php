<?php declare(strict_types=1);

/**
 * Platform capability: installer_panel
 * Namespace: Webkernel\BackOffice\Installer
 *
 * Responsibilities:
 *   Lifecycle — first-run installation wizard, post-install state resolution
 */
return [
    'label'       => 'Installer Panel',
    'description' => 'First-run installation wizard and setup flow',
    'version'     => '2.0.0',
    'active'      => true,
    'party'       => 'first',

    'namespace' => 'Webkernel\BackOffice\Installer',

    'providers' => [
        // InstallerPanelProvider is registered directly in WebApp::configure()
        // because it must be available before any platform loader runs.
    ],

    'helpers'       => [],
    'helpers_paths' => [],

    'route_groups' => [],
    'route_paths'  => [],

    'config_paths' => [],

    'view_namespaces' => [
        'webkernel-installer' => 'Resources/Views',
    ],

    'lang_paths' => [],

    'migration_paths' => [],
    'seeder_paths'    => [],

    'command_paths' => [],

    'livewire_paths' => [],

    'filament_paths' => [
        'Presentation',
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
