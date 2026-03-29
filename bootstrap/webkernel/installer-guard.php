<?php
declare(strict_types=1);

/**
 * Webkernel Installer Guard
 *
 * Place this file at: bootstrap/webkernel/installer-guard.php
 *
 * Called by composer.json post-autoload-dump instead of calling artisan directly:
 *
 *   "post-autoload-dump": [
 *       "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
 *       "@php bootstrap/webkernel/installer-guard.php package:discover"
 *   ]
 *
 * Behaviour:
 *   - WEBKERNEL_INSTALLER_MODE=1  → skip the artisan command silently
 *   - bootstrap/app.php missing   → skip silently (not yet bootstrapped)
 *   - otherwise                   → run php artisan <command> normally
 *
 * This prevents the fatal "Failed to open stream: bootstrap/app.php" error
 * that occurs when Composer runs post-autoload-dump during create-project,
 * before the project is in its final location.
 *
 * @package webkernel/webkernel
 * @license EPL-2.0
 */

// ── Guard 1: explicit installer mode env var ──────────────────────────────
if (getenv('WEBKERNEL_INSTALLER_MODE') === '1') {
    echo '[installer-guard] Skipping artisan call (WEBKERNEL_INSTALLER_MODE=1)' . PHP_EOL;
    exit(0);
}

// ── Guard 2: bootstrap/app.php not yet present ────────────────────────────
$bootstrapApp = dirname(__DIR__) . '/app.php';
if (!file_exists($bootstrapApp)) {
    echo '[installer-guard] Skipping artisan call (bootstrap/app.php not found — project not yet in final location)' . PHP_EOL;
    exit(0);
}

// ── Guard 3: .env not yet present ─────────────────────────────────────────
$envFile = dirname(dirname(__DIR__)) . '/.env';
if (!file_exists($envFile)) {
    echo '[installer-guard] Skipping artisan call (.env not yet present)' . PHP_EOL;
    exit(0);
}

// ── All guards passed: run the artisan command ────────────────────────────
$artisan  = dirname(dirname(__DIR__)) . '/artisan';
$phpBin   = PHP_BINARY ?: 'php';
$command  = (string) ($argv[1] ?? 'package:discover');

// Build args: any additional argv after [1] are passed through
$extra = array_slice($argv, 2);
$args  = array_map('escapeshellarg', $extra);

$cmd = escapeshellarg($phpBin)
    . ' ' . escapeshellarg($artisan)
    . ' ' . escapeshellarg($command)
    . (empty($args) ? '' : ' ' . implode(' ', $args))
    . ' --ansi';

passthru($cmd, $exitCode);
exit($exitCode);
