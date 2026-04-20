<?php declare(strict_types=1);
/**
 * =============================================================================
 *  Webkernel — Pre-boot Environment Guard
 *  bootstrap/webkernel/support/boot-actions/050-setup_env.php
 * =============================================================================
 *
 *  Responsibilities (this file only):
 *    - Detect IS_DEVMODE and, if active, register a step to remove the
 *      devmode file. The file path is never surfaced in the UI.
 *    - Run the fast-path check (.env + database.sqlite both present).
 *    - Register hard guards (PHP version, extensions, writability).
 *    - Declare and register each setup step.
 *    - Define the complete-page and post-setup redirect.
 *
 *  What this file does NOT do:
 *    - Token lifecycle         → SetupFlow (080-setup-flow.php)
 *    - Route registration      → SetupFlow
 *    - Page rendering          → SetupFlow / MicroWebPage
 *    - exit() calls            → SetupFlow
 *
 *  Devmode note:
 *    IS_DEVMODE is entirely separate from Laravel's APP_ENV.
 *    IS_DEVMODE controls Webkernel's own pre-boot diagnostic behaviour.
 *    APP_ENV controls Laravel's environment (caching, error detail, etc.).
 *    The two are orthogonal and must not be conflated.
 *
 *  BASE_PATH must be defined before this file is included.
 *  bootstrap/webkernel/support/boot-services/* must already be loaded.
 * =============================================================================
 */

(static function (): void {

    $envPath     = BASE_PATH . '/.env';
    $dbPath      = BASE_PATH . '/database/database.sqlite';
    $examplePath = BASE_PATH . '/.env.example';
    $dbDir       = dirname($dbPath);

    // ── Devmode detection ─────────────────────────────────────────────────
    // IS_DEVMODE is set by the upstream bootstrap layer when the devmode
    // activation file exists on disk. The file path stays local; it is
    // never forwarded to any page builder or UI component.
    $devmodeActive   = defined('IS_DEVMODE') && IS_DEVMODE === true;
    $devmodeFilePath = ($devmodeActive && defined('DEVMODE_FILE')) ? (string) DEVMODE_FILE : null;

    // ── Shared mutable state threaded through step closures ───────────────
    $state = new class {
        public string  $envContent = '';
        public ?string $appKey     = null;
    };

    // =========================================================================
    //  Build the setup flow
    // =========================================================================
    SetupFlow::create(BASE_PATH)
        ->scopeTo('setup')

        // ── Fast-path ────────────────────────────────────────────────────────
        // Both pre-boot sentinel files present → Laravel boots normally.
        ->fastPath(static fn (): bool => is_file($envPath) && is_file($dbPath))

        // ── Hard guards ──────────────────────────────────────────────────────
        ->guard(
            check:   static fn (): bool => PHP_VERSION_ID >= 80100,
            message: 'PHP 8.1 or newer is required.',
        )
        ->guard(
            check:   static fn (): bool => extension_loaded('pdo_sqlite'),
            message: 'The pdo_sqlite extension must be enabled.',
        )
        ->guard(
            check:   static fn (): bool => is_dir(BASE_PATH) && is_writable(BASE_PATH),
            message: 'The application root must be writable by the web server.',
        )
        ->guard(
            check:   static fn (): bool =>
                         function_exists('openssl_random_pseudo_bytes')
                         || function_exists('random_bytes'),
            message: 'An entropy source (openssl or random_bytes) must be available.',
        )

        // ── Step 0: disable devmode (conditional) ────────────────────────────
        // Injected only when IS_DEVMODE is active.
        // The operator sees a clear label; the file path is never shown in the UI.
        // Failure is non-fatal: setup continues regardless.
        ->when(
            $devmodeActive,
            static function (SetupFlow $f) use ($devmodeFilePath): void {
                $f->step(
                    label:   'Disable Webkernel devmode',
                    closure: static function () use ($devmodeFilePath): bool|string {
                        if ($devmodeFilePath === null || !is_file($devmodeFilePath)) {
                            return true; // already gone or path unknown
                        }
                        if (@unlink($devmodeFilePath)) {
                            return true;
                        }
                        return 'Devmode file could not be removed. Check server permissions.';
                    },
                );
            }
        )

        // ── Step 1: read environment template ────────────────────────────────
        ->step(
            label:   'Read environment template (.env.example)',
            closure: static function () use ($envPath, $examplePath, $state): bool|string {
                if (is_file($envPath)) return true;
                if (!is_file($examplePath)) { $state->envContent = ''; return true; }
                $content = @file_get_contents($examplePath);
                if ($content === false) return 'Cannot read .env.example — check file permissions.';
                $state->envContent = $content;
                return true;
            },
        )

        // ── Step 2: generate APP_KEY ──────────────────────────────────────────
        ->step(
            label:   'Generate secure application key (APP_KEY)',
            closure: static function () use ($envPath, $state): bool|string {
                if (is_file($envPath)) return true;
                try {
                    /** @disregard */
                    $raw = function_exists('openssl_random_pseudo_bytes')
                        ? (string) openssl_random_pseudo_bytes(32)
                        : random_bytes(32);
                } catch (\Throwable $e) {
                    return 'Entropy source failed: ' . $e->getMessage();
                }
                $state->appKey = 'base64:' . base64_encode($raw);
                return true;
            },
        )

        // ── Step 3: write .env ────────────────────────────────────────────────
        ->step(
            label:   'Write environment file (.env)',
            closure: static function () use ($envPath, $state): bool|string {
                if (is_file($envPath)) return true;
                if ($state->appKey === null) return 'APP_KEY was not generated — cannot write .env.';
                $content = $state->envContent;
                $key     = $state->appKey;
                $content = preg_match('/^APP_KEY=/m', $content)
                    ? (string) preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=' . $key, $content)
                    : "APP_KEY={$key}\n" . $content;
                if (@file_put_contents($envPath, $content) === false) {
                    return 'Write failed — check permissions on the application root.';
                }
                return true;
            },
        )

        // ── Step 4: create SQLite database file ───────────────────────────────
        ->step(
            label:   'Create SQLite database file',
            closure: static function () use ($dbPath, $dbDir): bool|string {
                if (is_file($dbPath)) return true;
                if (!is_dir($dbDir)) return 'The database/ directory does not exist — check project structure.';
                if (!@touch($dbPath)) return 'Cannot create database file — check permissions on the database directory.';
                return true;
            },
        )

        // ── Step 5: migrations (deferred) ─────────────────────────────────────
        ->pendingStep('Run database migrations (deferred to first boot)')

        // ── Complete page ──────────────────────────────────────────────────────
        ->completePage(
            title:       'Environment Ready',
            message:     '<b>The environment has been initialised successfully.</b>'
                       . ' Database migrations will run automatically on first boot.'
                       . ' Click the button below to continue to the application installer,'
                       . ' where you will create the first administrator account.',
            buttonLabel: 'Continue to Installer',
            redirectTo:  '/installer',
        )

        ->run();

})();
