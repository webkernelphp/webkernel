<?php
/** Webkernel catalog -- 2026-05-03T09:32:43+00:00 | v2 | fingerprint_changed | 1/1 */
return [
    'wk_version' => 2,
    'generated_at' => '2026-05-03T09:32:43+00:00',
    'fingerprint' => 'e944d4886f23ceacf2c006ee83c6f6b9455148c984b18cec575a97a288526033',
    'rebuilt_because' => 'fingerprint_changed',
    'total' => 1,
    'active' => 1,
    'psr4_map' => [
        'WebModule\\TamazightEducationTestWebkernelModule\\' => '/home/yassine/Projects/Webkernel/modules/github.com/tamazight-education/test-webkernel-module',
    ],
    'entries' => [
        [
            'id' => 'github-com::tamazight-education/test-webkernel-module',
            'label' => 'Test Webkernel Module (Tamazight Education)',
            'description' => 'A POC test module that is meant to be downloaded, updated ...',
            'version' => '0.1.0',
            'active' => true,
            'registry' => 'github.com',
            'vendor' => 'tamazight-education',
            'slug' => 'test-webkernel-module',
            'party' => 'first',
            'namespace' => 'WebModule\\TamazightEducationTestWebkernelModule',
            'author' => [
                'name' => 'Essaadia Hamouch',
                'email' => 'essadia@tamazight.education',
                'url' => 'https://tamazight.education/',
            ],
            'license' => 'proprietary',
            'providers' => [
                'WebModule\\TamazightEducationTestWebkernelModule\\Providers\\TestWebkernelModuleServiceProvider',
            ],
            'helpers' => [],
            'helpers_paths' => [],
            'route_groups' => [
                'web' => [
                    'routes/web.php',
                ],
                'api' => [
                    'routes/api.php',
                ],
            ],
            'route_paths' => [],
            'config_paths' => [
                'config',
            ],
            'view_namespaces' => [
                'tamazight-education-test-webkernel-module' => 'resources/views',
            ],
            'lang_paths' => [
                'tamazight-education-test-webkernel-module' => 'lang',
            ],
            'migration_paths' => [
                'database/migrations',
            ],
            'seeder_paths' => [
                'database/seeders',
            ],
            'command_paths' => [
                'src/Console',
            ],
            'livewire_paths' => [
                'src/Livewire',
            ],
            'filament_paths' => [
                'src/Filament',
            ],
            'asset_paths' => [
                'resources/assets',
            ],
            'blade_to_optimize_paths' => [
                [
                    'path' => 'resources/views',
                    'compile' => true,
                    'fold' => false,
                    'memo' => false,
                    'safe' => [],
                    'unsafe' => [],
                ],
            ],
            'depends' => [],
            'compatibility' => [
                'php' => '>=8.4',
                'laravel' => '>=12.0',
                'webkernel' => '>=1.0.0',
            ],
            'certification' => [
                'certified_at' => NULL,
                'certified_hash' => NULL,
            ],
            'created_at' => '2026-04-22T11:06:59+00:00',
            '_root' => '/home/yassine/Projects/Webkernel/modules/github.com/tamazight-education/test-webkernel-module',
            '_type' => 'module',
            '_registry' => 'github.com',
            '_vendor' => 'tamazight-education',
            '_slug' => 'test-webkernel-module',
        ],
    ],
];
