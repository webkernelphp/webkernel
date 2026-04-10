<?php
declare(strict_types=1);

/**
 * ═══════════════════════════════════════════════════════════════════
 *  WebKernel — Pre-boot Environment Guard
 * ═══════════════════════════════════════════════════════════════════
 *
 *  Runs before Laravel loads .env. When setup is needed the entire
 *  progress page — prerequisites, steps, and the "Open Application"
 *  button — is expressed as a single EmergencyPageBuilder chain.
 *
 *  Flow:
 *    1. Fast-path: both files exist → return immediately.
 *    2. Prerequisite guards → render a CRITICAL error page and stop
 *       if the server itself is misconfigured (wrong PHP, missing PDO,
 *       unwritable directory, no entropy source).
 *    3. Setup builder → each setup action is a ->step() closure.
 *       Closures run at ->render() time.
 *    4. ->submitStep('Open Application', '/') → emits a plain <a>
 *       link when every step passed; a neutral blocked notice when
 *       any step failed. No JS reload. No auto-refresh meta tag.
 *       The user clicks the link when they are ready.
 *    5. ->render() → sends the HTTP 200 setup page and terminates.
 *       The next request hits Laravel normally; migrations run via
 *       SystemManagerServiceProvider::boot().
 *
 *  Requirements:
 *    - BASE_PATH defined before inclusion.
 *    - EmergencyPageBuilder available (loaded in fast-boot.php).
 *
 * ═══════════════════════════════════════════════════════════════════
 */

(static function (): void {

    $envPath = BASE_PATH . '/.env';
    $dbPath  = BASE_PATH . '/database/database.sqlite';

    // ── §A Fast-path ──────────────────────────────────────────────
    // Both files exist → environment is ready → boot normally.

    if (is_file($envPath) && is_file($dbPath)) {
        return;
    }

    // ── §B Prerequisite guards ────────────────────────────────────
    // These checks run before the setup page opens. A failure here
    // means the server itself is misconfigured — the setup page would
    // be misleading, so we emit a CRITICAL error page instead.

    $prerequisites = [
        'PHP 8.1 or newer is required'
            => static fn(): bool => PHP_VERSION_ID >= 80100,

        'The pdo_sqlite extension must be enabled'
            => static fn(): bool => extension_loaded('pdo_sqlite'),

        'BASE_PATH must exist and be writable by the web server'
            => static fn(): bool => is_dir(BASE_PATH) && is_writable(BASE_PATH),

        'openssl_random_pseudo_bytes or random_bytes must be available'
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

    // ── §C Capture values for closures ────────────────────────────
    // Closures inside ->step() close over these variables. We resolve
    // them once here so each closure stays focused on a single action.

    $examplePath = BASE_PATH . '/.env.example';
    $dbDir       = dirname($dbPath);

    // Shared mutable state passed by reference into closures.
    // This lets step closures communicate results to later steps
    // without global variables.
    $state = [
        'envContent' => '',
        'appKey'     => null,
    ];

    // ── §D Build and render the setup page ───────────────────────
    // Every action is declared as a ->step() closure. Steps are
    // executed in order inside ->render(). The chain reads like
    // a plain description of what the setup does.

    EmergencyPageBuilder::create()
        ->title('Initial Setup')
        ->severity('SETUP')
        ->code(200)
        ->systemState('SETTING UP YOUR ENVIRONMENT')
        ->footer('WEBKERNEL (BY NUMERIMONDES) — FIRST-RUN SETUP')

        // ── Step 0: read template ─────────────────────────────────
        ->step(
            label: 'Reading environment template',
            closure: static function () use ($envPath, $examplePath, &$state): bool|string {
                if (is_file($envPath)) {
                    return true; // already present — nothing to read
                }

                if (!is_file($examplePath)) {
                    // No template — start from scratch; not a hard failure
                    $state['envContent'] = '';
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
            label: 'Generating secure application key',
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
                    return "Write failed — check directory permissions on " . BASE_PATH;
                }

                return true;
            },
        )

        // ── Step 3: create database file ──────────────────────────
        ->step(
            label: 'Initialising SQLite database file',
            closure: static function () use ($dbPath, $dbDir): bool|string {
                if (is_file($dbPath)) {
                    return true;
                }

                if (!is_dir($dbDir)) {
                    return "The database/ directory does not exist — check project structure.";
                }

                if (!@touch($dbPath)) {
                    return "Cannot create database file — check permissions on {$dbDir}";
                }

                return true;
            },
        )

        // ── Step 4: migrations (deferred, no closure) ─────────────
        // Marked pending: true — shown as ⋯, not executed here.
        // SystemManagerServiceProvider handles this on the next boot.
        ->step(
            label: 'Running database migrations',
            pending: true,
        )

        // ── Submit step ───────────────────────────────────────────
        // Renders as a plain <a> link pointing to / only when every
        // non-pending step returned true. If any step failed, a
        // "Setup incomplete" notice appears instead.
        ->submitStep('Open Application', '/')

        ->render();

})();
