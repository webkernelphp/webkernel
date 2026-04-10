<?php declare(strict_types=1);

/**
 * ═══════════════════════════════════════════════════════════════════
 *  WebKernel — Pre-boot Environment Guard
 * ═══════════════════════════════════════════════════════════════════
 *
 *  Two-phase setup — nothing is written until the user confirms.
 *
 *  Phase 1  GET /
 *    All steps shown as ⋯ pending. No files touched.
 *    submitStep → "Proceed with Setup" → /?webkernel_setup=1
 *
 *  Phase 2  GET /?webkernel_setup=1
 *    Step closures execute in order. Results shown (✓ / ✕).
 *    submitStep → "Open Application" → / (RootController takes over)
 *
 *  RootController is the master once Laravel boots: it redirects to
 *  /installer (not fully installed) or /system (installed).
 *  setup_env.php only ensures the minimum pre-boot requirements exist
 *  (.env with APP_KEY, empty database.sqlite) so that Laravel can
 *  actually reach RootController in the first place.
 *
 *  Prerequisite guards run in both phases. A hard server error
 *  (wrong PHP, missing PDO, unwritable base path) renders a CRITICAL
 *  page immediately — the setup page would be misleading in that case.
 *
 *  Requirements:
 *    BASE_PATH defined · EmergencyPageBuilder loaded (fast-boot.php)
 * ═══════════════════════════════════════════════════════════════════
 */

(static function (): void {

    $envPath = BASE_PATH . '/.env';
    $dbPath  = BASE_PATH . '/database/database.sqlite';

    // ── §A Fast-path ──────────────────────────────────────────────
    // Both pre-boot files exist → let Laravel boot normally.
    // RootController decides what happens next.

    if (is_file($envPath) && is_file($dbPath)) {
        return;
    }

    // ── §B Prerequisite guards ────────────────────────────────────
    // Hard server conditions checked before showing any setup page.
    // These represent problems the setup wizard cannot fix itself.

    $prerequisites = [
        'PHP 8.1 or newer is required'
            => static fn(): bool => PHP_VERSION_ID >= 80100,

        'The pdo_sqlite extension must be enabled'
            => static fn(): bool => extension_loaded('pdo_sqlite'),

        'The application root must be writable by the web server'
            => static fn(): bool => is_dir(BASE_PATH) && is_writable(BASE_PATH),

        'An entropy source (openssl or random_bytes) must be available'
            => static fn(): bool =>
                function_exists('openssl_random_pseudo_bytes')
                || function_exists('random_bytes'),
    ];

    foreach ($prerequisites as $description => $check) {
        if (!$check()) {
            EmergencyPageBuilder::create()
                ->title('Setup Cannot Proceed')
                ->message(
                    "A server prerequisite is not satisfied:\n\n"
                    . "  ✕  {$description}\n\n"
                    . "Correct the server configuration and reload this page."
                )
                ->severity('CRITICAL')
                ->code(500)
                ->systemState('ENVIRONMENT ERROR')
                ->footer('SERVER CONFIGURATION ERROR — SETUP BLOCKED')
                ->addButton('Reload', '/')
                ->render();
        }
    }

    // ── §C Resolve paths used by closures ─────────────────────────

    $examplePath = BASE_PATH . '/.env.example';
    $dbDir       = dirname($dbPath);

    // ── §D Confirmation gate ──────────────────────────────────────
    // Phase 1: user has not yet confirmed → show the preview page.
    // All steps are declared pending (no closures execute here).
    // submitStep emits a plain <a> to /?webkernel_setup=1.

    $confirmed = (($_GET['webkernel_setup'] ?? '') === '1');

    if (!$confirmed) {
        EmergencyPageBuilder::create()
            ->title('First-run Setup Required')
            ->severity('SETUP')
            ->code(200)
            ->systemState('FIRST-RUN SETUP')
            ->footer('WEBKERNEL (BY NUMERIMONDES) — REVIEW AND CONFIRM BEFORE PROCEEDING')
            ->message(
                "<b>This application has not been initialised yet.</b>"
                . "&nbsp;The following actions will be performed on this server."
                . "&nbsp;Review them, then click Proceed when ready."
            )

            // All pending — nothing executed, nothing written
            ->step('Read environment template (.env.example)',  pending: true)
            ->step('Generate a secure application key (APP_KEY)', pending: true)
            ->step('Write environment file (.env)',              pending: true)
            ->step('Create SQLite database file',               pending: true)
            ->step('Run database migrations (on next boot)',    pending: true)

            // submitStep renders as a plain <a> link — no JS
            ->submitStep('Proceed with Setup', '/?webkernel_setup=1')
            ->render();
        // render() is never-return — execution stops here
    }

    // ── §E Phase 2: run the setup ─────────────────────────────────
    // User confirmed. Step closures execute in order inside render().
    // Shared mutable state lets closures pass results to later steps.

    $state = [
        'envContent' => '',
        'appKey'     => null,
    ];

    EmergencyPageBuilder::create()
        ->title('Setup in Progress')
        ->severity('SETUP')
        ->code(200)
        ->systemState('SETTING UP YOUR ENVIRONMENT')
        ->footer('WEBKERNEL (BY NUMERIMONDES) — FIRST-RUN SETUP')

        // ── Step 0: read template ─────────────────────────────────
        ->step(
            label: 'Reading environment template',
            closure: static function () use ($envPath, $examplePath, &$state): bool|string {
                if (is_file($envPath)) {
                    return true; // already present — skip
                }

                if (!is_file($examplePath)) {
                    $state['envContent'] = ''; // will create minimal .env
                    return true;
                }

                $content = @file_get_contents($examplePath);

                if ($content === false) {
                    return "Could not read {$examplePath} — check file permissions.";
                }

                $state['envContent'] = $content;
                return true;
            },
        )

        // ── Step 1: generate APP_KEY ──────────────────────────────
        ->step(
            label: 'Generating secure application key (APP_KEY)',
            closure: static function () use ($envPath, &$state): bool|string {
                if (is_file($envPath)) {
                    return true; // key already set in existing .env
                }

                try {
                    $raw = function_exists('openssl_random_pseudo_bytes')
                        ? (string) openssl_random_pseudo_bytes(32)
                        : random_bytes(32);
                } catch (\Throwable $e) {
                    return 'Entropy source failed: ' . $e->getMessage();
                }

                $state['appKey'] = 'base64:' . base64_encode($raw);
                return true;
            },
        )

        // ── Step 2: write .env ────────────────────────────────────
        ->step(
            label: 'Writing environment file (.env)',
            closure: static function () use ($envPath, &$state): bool|string {
                if (is_file($envPath)) {
                    return true;
                }

                if ($state['appKey'] === null) {
                    return 'APP_KEY was not generated — cannot write .env.';
                }

                $content = $state['envContent'];
                $key     = $state['appKey'];

                $content = preg_match('/^APP_KEY=/m', $content)
                    ? (string) preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=' . $key, $content)
                    : "APP_KEY={$key}\n" . $content;

                if (@file_put_contents($envPath, $content) === false) {
                    return 'Write failed — check directory permissions on ' . BASE_PATH;
                }

                return true;
            },
        )

        // ── Step 3: create database file ──────────────────────────
        ->step(
            label: 'Creating SQLite database file',
            closure: static function () use ($dbPath, $dbDir): bool|string {
                if (is_file($dbPath)) {
                    return true;
                }

                if (!is_dir($dbDir)) {
                    return 'The database/ directory does not exist — check project structure.';
                }

                if (!@touch($dbPath)) {
                    return "Cannot create database file — check permissions on {$dbDir}";
                }

                return true;
            },
        )

        // ── Step 4: migrations (deferred) ─────────────────────────
        // Marked pending — SystemManagerServiceProvider handles this
        // via Artisan::call('migrate') on the first full boot.
        ->step(
            label: 'Running database migrations',
            pending: true,
        )

        // ── Submit ────────────────────────────────────────────────
        // Link appears only when every non-pending step returned true.
        // RootController then redirects to /installer or /system.
        ->submitStep('Open Application', '/')

        ->render();

})();
