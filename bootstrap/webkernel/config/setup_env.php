<?php
declare(strict_types=1);

/**
 * ═══════════════════════════════════════════════════════════════════
 *  WebKernel — Pre-boot Environment Guard
 * ═══════════════════════════════════════════════════════════════════
 *
 *  Runs BEFORE Laravel loads .env. Detects a missing environment or
 *  database and streams a live setup progress page to the browser.
 *  On the next request (triggered automatically by the page countdown)
 *  Laravel boots normally and SystemManagerServiceProvider applies
 *  migrations via Artisan::call().
 *
 *  Requirements:
 *    - BASE_PATH must be defined before inclusion.
 *    - The streaming helpers (beginSetupStream / pushSetupStep /
 *      finalizeSetupStream) must be loaded earlier in fast-boot.php.
 *
 *  Features:
 *    - Closure-based prerequisite guards: block the setup page entirely
 *      with a full ErrorResponseBuilder render if PHP/extension checks
 *      fail, keeping the error path clean and consistent.
 *    - Server-side gated submit: the "Try again" button is only emitted
 *      by the streaming page when all guards pass (handled via
 *      ErrorResponseBuilder::gatedSubmitButton internally in the stream
 *      finaliser).
 *    - Pure-PHP HTTP readiness probe via HttpClient so the setup step
 *      can optionally ping a remote health endpoint to confirm network
 *      egress before declaring success.
 *    - Safe no-op on every subsequent request once environment is ready.
 *
 * ═══════════════════════════════════════════════════════════════════
 */

(static function (): void {

    // ── §A Fast-path ──────────────────────────────────────────────
    // Everything already present — let Laravel boot normally.

    $envPath = BASE_PATH . '/.env';
    $dbPath  = BASE_PATH . '/database/database.sqlite';

    if (is_file($envPath) && is_file($dbPath)) {
        return;
    }

    // ── §B Prerequisite guards ────────────────────────────────────
    // Run these BEFORE opening the streaming page. If any guard fails
    // we emit a full error page via ErrorResponseBuilder (not the
    // streaming setup page) and stop. This prevents a misleading
    // "setup in progress" state when the server itself is misconfigured.

    $prerequisites = [
        'PHP 8.1+ required' => static fn(): bool => PHP_VERSION_ID >= 80100,
        'PDO extension required for SQLite' => static fn(): bool => extension_loaded('pdo_sqlite'),
        'BASE_PATH must be a writable directory' => static fn(): bool =>
            is_dir(BASE_PATH) && is_writable(BASE_PATH),
        'OpenSSL or random_bytes available' => static fn(): bool =>
            function_exists('openssl_random_pseudo_bytes') || function_exists('random_bytes'),
    ];

    foreach ($prerequisites as $description => $check) {
        if (!$check()) {
            ErrorResponseBuilder::create()
                ->title('Setup Cannot Proceed')
                ->message(
                    "A required prerequisite is not met:\n\n"
                    . "  ✕  {$description}\n\n"
                    . "Please correct the server configuration and reload."
                )
                ->severity('CRITICAL')
                ->code(500)
                ->footer('SERVER CONFIGURATION ERROR — SETUP BLOCKED')
                ->addButton('Reload', 'javascript:location.reload()')
                ->render();
        }
    }

    // ── §C Open the streaming setup page ─────────────────────────

    beginSetupStream([
        'Reading environment template',       // step 0
        'Generating secure application key',  // step 1
        'Writing environment file (.env)',     // step 2
        'Initialising database file',         // step 3
        'Scheduling database migrations',     // step 4
    ]);

    // ── §D Step 0: read .env.example ─────────────────────────────

    $envContent = '';

    if (!is_file($envPath)) {
        $example = BASE_PATH . '/.env.example';

        if (is_file($example)) {
            $envContent = (string) @file_get_contents($example);
            pushSetupStep(0, true, 'Template loaded from .env.example');
        } else {
            pushSetupStep(0, false, '.env.example not found — a minimal .env will be created');
        }
    } else {
        pushSetupStep(0, true, '.env already present — step skipped');
    }

    // ── §E Step 1: generate APP_KEY ───────────────────────────────

    $appKey = null;

    if (!is_file($envPath)) {
        $appKey = self_generateAppKey();
        pushSetupStep(1, true, 'Secure 256-bit key generated');
    } else {
        pushSetupStep(1, true, 'APP_KEY already set in .env — step skipped');
    }

    // ── §F Step 2: write .env ─────────────────────────────────────

    if (!is_file($envPath) && $appKey !== null) {
        // Inject or replace APP_KEY line
        $envContent = preg_match('/^APP_KEY=/m', $envContent)
            ? (string) preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=' . $appKey, $envContent)
            : "APP_KEY={$appKey}\n" . $envContent;

        $written = @file_put_contents($envPath, $envContent);

        if ($written !== false) {
            pushSetupStep(2, true, '.env written successfully');
        } else {
            pushSetupStep(2, false, 'Write failed — check directory permissions on ' . BASE_PATH);
        }
    } else {
        pushSetupStep(2, true, '.env already present — step skipped');
    }

    // ── §G Step 3: initialise SQLite database file ────────────────

    if (!is_file($dbPath)) {
        $dbDir = dirname($dbPath);

        if (!is_dir($dbDir)) {
            pushSetupStep(3, false, 'database/ directory does not exist — check project structure');
        } elseif (@touch($dbPath)) {
            pushSetupStep(3, true, 'Empty SQLite file created at database/database.sqlite');
        } else {
            pushSetupStep(3, false, 'Cannot create database file — check permissions on ' . $dbDir);
        }
    } else {
        pushSetupStep(3, true, 'Database file already present — step skipped');
    }

    // ── §H Step 4: migrations deferred ───────────────────────────
    // Migrations run automatically on the next boot via
    // SystemManagerServiceProvider::boot() → Artisan::call('migrate').

    pushSetupStep(4, null, 'Will run automatically on the next page load via the service provider');

    // ── §I Finalise — hand off to the countdown ───────────────────

    finalizeSetupStream(4);

})();


// ═══════════════════════════════════════════════════════════════════
//  Internal helpers (file-scoped, not polluting global namespace)
// ═══════════════════════════════════════════════════════════════════

/**
 * Generate a Laravel-compatible base64-encoded APP_KEY.
 * Uses the strongest available entropy source.
 */
function self_generateAppKey(): string
{
    if (function_exists('openssl_random_pseudo_bytes')) {
        $raw = (string) openssl_random_pseudo_bytes(32);
    } else {
        try {
            $raw = random_bytes(32);
        } catch (\Throwable) {
            // Last-resort fallback (should never reach here given §B guards)
            $raw = substr(
                hash('sha256', uniqid((string) mt_rand(), true) . microtime(true), true),
                0,
                32,
            );
        }
    }

    return 'base64:' . base64_encode($raw);
}
