<?php
declare(strict_types=1);
/**
 * ═══════════════════════════════════════════════════════════════════
 *  Webkernel — Pre-boot Environment Guard
 *  bootstrap/webkernel/config/setup_env.php
 * ═══════════════════════════════════════════════════════════════════
 *
 *  This file is intentionally thin. All wizard logic (token lifecycle,
 *  route registration, page rendering, redirect fallback) lives inside
 *  SetupFlow in renderCriticalErrorHtml.php.
 *
 *  What lives here:
 *    - The fast-path condition (both files exist → Laravel boots normally)
 *    - Hard server guards (PHP version, extensions, writability…)
 *    - The step declarations and their closures
 *    - The complete-page definition
 *    - The post-setup redirect target
 *
 *  Nothing else. No token code. No router calls. No exit().
 *
 *  BASE_PATH must be defined before this file is included.
 *  renderCriticalErrorHtml.php must already be loaded.
 * ═══════════════════════════════════════════════════════════════════
 */
(static function (): void {

    $envPath     = BASE_PATH . '/.env';
    $dbPath      = BASE_PATH . '/database/database.sqlite';
    $examplePath = BASE_PATH . '/.env.example';
    $dbDir       = dirname($dbPath);

    // Shared mutable state threaded through step closures.
    // Using an object avoids reference-capture verbosity.
    $state = new class {
        public string  $envContent = '';
        public ?string $appKey     = null;
    };

    SetupFlow::create(BASE_PATH)

        // ── Fast-path ─────────────────────────────────────────────
        // Both pre-boot files exist → return immediately.
        // Laravel boots normally. RootController takes over.
        ->fastPath(static fn(): bool => is_file($envPath) && is_file($dbPath))

        // ── Hard guards ───────────────────────────────────────────
        // Conditions the wizard cannot fix. Render CRITICAL and stop.
        ->guard(
            check:    static fn(): bool => PHP_VERSION_ID >= 80100,
            message:  'PHP 8.1 or newer is required',
        )
        ->guard(
            check:    static fn(): bool => extension_loaded('pdo_sqlite'),
            message:  'The pdo_sqlite extension must be enabled',
        )
        ->guard(
            check:    static fn(): bool => is_dir(BASE_PATH) && is_writable(BASE_PATH),
            message:  'The application root must be writable by the web server',
        )
        ->guard(
            check:    static fn(): bool =>
                          function_exists('openssl_random_pseudo_bytes')
                          || function_exists('random_bytes'),
            message:  'An entropy source (openssl or random_bytes) must be available',
        )

        // ── Step 0: read environment template ─────────────────────
        ->step(
            label:   'Read environment template (.env.example)',
            closure: static function () use ($envPath, $examplePath, $state): bool|string {
                if (is_file($envPath)) {
                    return true; // Already exists — skip silently.
                }
                if (!is_file($examplePath)) {
                    $state->envContent = '';
                    return true; // No template — that's fine, we'll write a minimal .env.
                }
                $content = @file_get_contents($examplePath);
                if ($content === false) {
                    return "Cannot read {$examplePath} — check file permissions.";
                }
                $state->envContent = $content;
                return true;
            },
        )

        // ── Step 1: generate APP_KEY ───────────────────────────────
        ->step(
            label:   'Generate secure application key (APP_KEY)',
            closure: static function () use ($envPath, $state): bool|string {
                if (is_file($envPath)) {
                    return true; // Already exists — skip silently.
                }
                try {
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

        // ── Step 2: write .env ─────────────────────────────────────
        ->step(
            label:   'Write environment file (.env)',
            closure: static function () use ($envPath, $state): bool|string {
                if (is_file($envPath)) {
                    return true;
                }
                if ($state->appKey === null) {
                    return 'APP_KEY was not generated — cannot write .env.';
                }
                $content = $state->envContent;
                $key     = $state->appKey;
                $content = preg_match('/^APP_KEY=/m', $content)
                    ? (string) preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=' . $key, $content)
                    : "APP_KEY={$key}\n" . $content;

                if (@file_put_contents($envPath, $content) === false) {
                    return 'Write failed — check permissions on ' . BASE_PATH;
                }
                return true;
            },
        )

        // ── Step 3: create database file ───────────────────────────
        ->step(
            label:   'Create SQLite database file',
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

        // ── Step 4: migrations (deferred) ──────────────────────────
        // Shown on all pages as a pending step — runs at framework boot.
        ->pendingStep('Run database migrations (deferred to first boot)')

        // ── Complete page ──────────────────────────────────────────
        ->completePage(
            title:       'Setup Complete',
            message:     '<b>The environment has been initialised successfully.</b>'
                       . ' Database migrations will run automatically on first boot.'
                       . ' Click the button below to open the application.',
            buttonLabel: 'Open Application',
        )

        // ── Post-setup redirect ────────────────────────────────────
        ->redirectThenTo('/')

        // ── Dispatch ───────────────────────────────────────────────
        // If the current URL matches a setup route → handle + exit.
        // If not → redirect to the preview page (setup still needed).
        // If fast-path returned true → return here, Laravel boots.
        ->run();

})();
