<?php declare(strict_types=1);

/**
 * Pre-boot environment guard — runs before Laravel loads .env.
 *
 * Handles the two things that must exist before the framework can boot:
 *   1. .env file (copies .env.example and generates a secure APP_KEY)
 *   2. SQLite database file (touch so Laravel can open the connection)
 *
 * Migrations are NOT run here — they are handled by
 * SystemManagerServiceProvider via app()->booted() + Artisan::call(),
 * which is the correct Laravel-idiomatic approach.
 *
 * Requires BASE_PATH to be defined before inclusion.
 * Safe no-op on every request once the environment is configured.
 */
(static function (): void {

    // ── 1. .env ───────────────────────────────────────────────────────────────
    $env = BASE_PATH . '/.env';
    if (!is_file($env)) {
        $example = BASE_PATH . '/.env.example';
        $content = is_file($example) ? ((string) @file_get_contents($example)) : '';

        // Generate APP_KEY — openssl preferred; sodium may be broken on some hosts
        if (function_exists('openssl_random_pseudo_bytes')) {
            $raw = (string) openssl_random_pseudo_bytes(32);
        } else {
            try {
                $raw = random_bytes(32);
            } catch (\Throwable) {
                $raw = substr(hash('sha256', uniqid((string) mt_rand(), true) . microtime(true), true), 0, 32);
            }
        }
        $key = 'base64:' . base64_encode($raw);

        $content = preg_match('/^APP_KEY=/m', $content)
            ? (string) preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=' . $key, $content)
            : "APP_KEY={$key}\n" . $content;

        @file_put_contents($env, $content);
    }

    // ── 2. SQLite database file ───────────────────────────────────────────────
    $dbPath = BASE_PATH . '/database/database.sqlite';
    if (!is_file($dbPath) && is_dir(dirname($dbPath))) {
        @touch($dbPath);
    }

})();
