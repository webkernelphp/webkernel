<?php declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Platform boot path constants consumed by Webkernel\Base\Arcanes\Platform
|--------------------------------------------------------------------------
|
| This file is loaded by 010-paths.php glob after all sibling path files
| have already run (010 through 016). Every constant this file references
| (WEBKERNEL_PATH, WEBKERNEL_SUPPORT_PATH, WEBKERNEL_ERRORS_PAGES_PATH,
| etc.) is therefore guaranteed to be defined already.
|
| Naming convention
| -----------------
|   WEBKERNEL_HELPER_PATHS      list<string>              directories
|   WEBKERNEL_ROUTE_PATHS       list<array{group,file}>   route specs
|   WEBKERNEL_CONFIG_PATH       string                    single directory
|   WEBKERNEL_VIEW_NAMESPACES   array<handle,path>        handle => path
|   WEBKERNEL_LANG_PATHS        array<handle,path>        handle => path
|   WEBKERNEL_MIGRATION_PATHS   list<string>              extra paths
|   WEBKERNEL_LIVEWIRE_PATHS    array<namespace,dir>      namespace => dir
|   WEBKERNEL_COMMAND_PATHS     list<string>              directories
|   WEBKERNEL_BLAZE_PATHS       list<array<string,mixed>> blaze specs
|
*/

// bootHelpers() -- directories, every *.php inside is require_once'd
\defined('WEBKERNEL_HELPER_PATHS') || \define('WEBKERNEL_HELPER_PATHS', [
    WEBKERNEL_SUPPORT_PATH . '/platform-helpers',
]);

// bootRoutes() -- each spec carries a middleware group and an absolute file path
\defined('WEBKERNEL_ROUTE_PATHS') || \define('WEBKERNEL_ROUTE_PATHS', [
    ['group' => 'web', 'file' => WEBKERNEL_PATH . '/routes/web.php'],
    ['group' => 'api', 'file' => WEBKERNEL_PATH . '/routes/api.php'],
]);

// bootConfig() -- single directory, every *.php is merged under its stem key
\defined('WEBKERNEL_CONFIG_PATH') || \define('WEBKERNEL_CONFIG_PATH',
    WEBKERNEL_PATH . '/config'
);

// bootViews() -- handle => absolute path
// 'layup' is intentionally absent here: it uses resource_path() which requires
// the Laravel application to be booted; ViewPathsAndComponents handles it directly.
\defined('WEBKERNEL_VIEW_NAMESPACES') || \define('WEBKERNEL_VIEW_NAMESPACES', [
    'webkernel'             => WEBKERNEL_SUPPORT_PATH . '/views',
    'webkernel-quick-touch' => WEBKERNEL_PATH . '/codebase/Base/QuickTouch',
    'errors'                => WEBKERNEL_ERRORS_PAGES_PATH,
]);

// bootLang() -- handle => absolute path
\defined('WEBKERNEL_LANG_PATHS') || \define('WEBKERNEL_LANG_PATHS', [
    'webkernel' => WEBKERNEL_PATH . '/resources/lang',
]);

// bootMigrations() -- extra paths stacked on top of the hardcoded core path
// (WEBKERNEL_PATH/database/migrations is always loaded first by Platform itself)
// Leave empty unless the host application needs to inject additional paths here.
\defined('WEBKERNEL_MIGRATION_PATHS') || \define('WEBKERNEL_MIGRATION_PATHS', []);

// bootLivewire() -- namespace => absolute directory
\defined('WEBKERNEL_LIVEWIRE_PATHS') || \define('WEBKERNEL_LIVEWIRE_PATHS', [
    'Webkernel\\Livewire' => WEBKERNEL_PATH . '/codebase/Livewire',
]);

// bootCommands() -- directories, every *.php is inspected for Command subclasses
\defined('WEBKERNEL_COMMAND_PATHS') || \define('WEBKERNEL_COMMAND_PATHS', [
    WEBKERNEL_PATH . '/codebase/Console/Commands',
]);

// bootBlaze() -- each spec maps to one optimizer->in() call
\defined('WEBKERNEL_BLAZE_PATHS') || \define('WEBKERNEL_BLAZE_PATHS', [
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
