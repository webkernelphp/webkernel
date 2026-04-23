<?php declare(strict_types=1);

// -- PSR-4: Webkernel Sovereign Autoloader ------------------------------------
//
// @link https://php.net/manual/en/function.spl-autoload-register.php
//
// High-performance PSR-4 implementation for air-gapped environment stability.
// Handles multiple base directories and ensures absolute path resolution.
// This autoloader is prepended to the stack to ensure sovereign packages
// override vendor-space dependencies.

/** @disregard */
spl_autoload_register(static function (string $class): void {
    static $prefixes = null;

    if ($prefixes === null) {
        $prefixes = array_merge(
            [
                /* Core Layer: Connectors */

                // General contracts & facades (directly in the connectors package namespace)
                'Webkernel\\Connectors\\Contracts\\' => WEBKERNEL_PATH . '/aptitudes/connectors/contracts',
                'Webkernel\\Connectors\\Traits\\'    => WEBKERNEL_PATH . '/aptitudes/connectors/traits',
                'Webkernel\\Connectors\\'            => WEBKERNEL_PATH . '/aptitudes/connectors/facades',

                // Domain-specific connectors
                'Webkernel\\Communication\\' => WEBKERNEL_PATH . '/aptitudes/connectors/src/communication',
                'Webkernel\\Social\\'        => WEBKERNEL_PATH . '/aptitudes/connectors/src/social',
                'Webkernel\\Payment\\'       => WEBKERNEL_PATH . '/aptitudes/connectors/src/payment',
                'Webkernel\\Integration\\'   => WEBKERNEL_PATH . '/aptitudes/connectors/src/integration',
                'Webkernel\\Productivity\\'  => WEBKERNEL_PATH . '/aptitudes/connectors/src/productivity',
                'Webkernel\\FFI\\'           => WEBKERNEL_PATH . '/aptitudes/connectors/src/native/ffi',

                /* Aptitudes Layer: Core Business Logic */
                'Webkernel\\Async\\'         => WEBKERNEL_PATH . '/aptitudes/async',
                'Webkernel\\Components\\'    => WEBKERNEL_PATH . '/aptitudes/components',
                'Webkernel\\Pages\\'         => WEBKERNEL_PATH . '/aptitudes/pages',
                'Webkernel\\Panels\\'        => WEBKERNEL_PATH . '/aptitudes/panels',
                'Webkernel\\Plugins\\'       => WEBKERNEL_PATH . '/aptitudes/plugins',
                'Webkernel\\Traits\\'        => WEBKERNEL_PATH . '/aptitudes/traits',
                'Webkernel\\Generators\\'    => WEBKERNEL_PATH . '/aptitudes/generators',
                'Webkernel\\Query\\'         => WEBKERNEL_PATH . '/aptitudes/query-builder',

                /* Instance Layer: High-Level Business Logic */
                'Webkernel\\Businesses\\'    => WEBKERNEL_PATH . '/aptitudes/instance/businesses',
                'Webkernel\\Users\\'         => WEBKERNEL_PATH . '/aptitudes/instance/users',

                /* Backend Layer: System Infrastructure */
                'Webkernel\\System\\'        => WEBKERNEL_PATH . '/platform/_backend',

                /* Platform Layer */
                'Webkernel\\Arcanes\\'       => WEBKERNEL_PATH . '/platform/arcanes',
                'Webkernel\\QuickTouch\\'    => WEBKERNEL_PATH . '/support/quick_touch',
                'Webkernel\\Routes\\'        => WEBKERNEL_PATH . '/platform/routing',

                /* System Panel Specific Assets */
                'Webkernel\\BackOffice\\System\\'       => WEBKERNEL_PATH . '/platform/_back_office/sys_core_panel',
                'Webkernel\\BackOffice\\Businesses\\'   => WEBKERNEL_PATH . '/platform/_back_office/sys_biz_panel',
                'Webkernel\\BackOffice\\Installer\\'    => WEBKERNEL_PATH . '/platform/_back_office/installer_panel',


                /* Support Layer */

                /* Application Data Models */
                'App\\Models\\'              => WEBKERNEL_PATH . '/support/boot/app-models',

                /* Commands and Providers */
                'Webkernel\\Providers\\'     => WEBKERNEL_PATH . '/platform/providers',
                'Webkernel\\Commands\\'            => WEBKERNEL_PATH . '/support/commands',
            ],
            WEBKERNEL_DEV_NAMESPACES,
            [
                /* Fallback: Generic Webkernel Namespace */
                'Webkernel\\'           => WEBKERNEL_PATH . '/src',
            ]
        );
    }

    foreach ($prefixes as $prefix => $baseDirs) {
        // Ensure exact namespace match
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        // Calculate relative class name by removing the prefix
        $relativeClass = substr($class, strlen($prefix));

        // Convert namespace separators to directory separators
        $normalizedPath = str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

        foreach ((array) $baseDirs as $baseDir) {
            // Build absolute path
            $file = rtrim((string) $baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $normalizedPath;

            if (is_file($file)) {
                require_once $file;
                return;
            }
        }
    }
}, true, true);
