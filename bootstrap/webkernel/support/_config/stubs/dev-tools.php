<?php declare(strict_types=1);

/**
 * Dev Tools Configuration
 *
 * This file is optional.
 * If present, it enables dev mode and allows mapping custom namespaces
 * outside of the core Webkernel.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Dev Mode
    |--------------------------------------------------------------------------
    |
    | When enabled:
    | - Activates IS_DEVMODE
    | - Enables loading of custom namespaces (below)
    | - Can be used to toggle debug behavior in your app
    |
    */
    'dev-mode' => false,

    /*
    |--------------------------------------------------------------------------
    | Custom PSR-4 Namespaces
    |--------------------------------------------------------------------------
    |
    | Define additional namespaces for development.
    | Paths are relative to BASE_PATH.
    |
    | Example:
    | 'MyPackage\\' => 'packages/my-package/src'
    |
    */
    'namespaces' => [

        // Override or extend core parts safely

        // 'Webkernel\\System\\' => 'overrides/system',
        // 'Webkernel\\Panel\\'  => 'overrides/panel',

        // Custom packages
        // 'MyCompany\\Core\\'   => 'packages/core/src',
        // 'MyCompany\\UI\\'     => 'packages/ui/src',

        // Experimental modules
        // 'Sandbox\\'           => 'sandbox',

    ],

];
