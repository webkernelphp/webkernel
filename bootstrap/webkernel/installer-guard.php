<?php
declare(strict_types=1);
/**
 * Webkernel Installer Guard
 *
 * Place this file at: bootstrap/webkernel/installer-guard.php
 *
 * # default (package:discover)
 *  php bootstrap/webkernel/installer-guard.php
 *  php bootstrap/webkernel/installer-guard.php package:discover [args...]
 *
 *  # fix permissions
 *  php bootstrap/webkernel/installer-guard.php fix-permissions
 *
 *  # ensure storage dirs
 *  php bootstrap/webkernel/installer-guard.php ensure-storage-dirs
 *
 *  # run command as project owner
 *  php bootstrap/webkernel/installer-guard.php run-as-owner <cmd> [args...]
 *
 *  # skip artisan via env
 *  WEBKERNEL_INSTALLER_MODE=1 php bootstrap/webkernel/installer-guard.php
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
 * Sub-commands (passed as first argv):
 *   package:discover (default)    → run php artisan package:discover
 *   fix-permissions               → ensure storage dirs exist, fix ownership +
 *                                   permissions using PHP built-ins
 *                                   (no sudo, no shell — works as root or project owner)
 *   ensure-storage-dirs           → create all required Laravel/Webkernel storage
 *                                   directories without touching ownership/permissions
 *   run-as-owner <cmd…>           → re-exec a command as the project owner
 *                                   (root → posix_setuid; same user → direct)
 *
 * @package webkernel/webkernel
 * @license EPL-2.0
 */

$subCommand = $argv[1] ?? '';

// ── Route: fix-permissions ────────────────────────────────────────────────
if ($subCommand === 'fix-permissions') {
    fixPermissions();
    exit(0);
}

// ── Route: ensure-storage-dirs ───────────────────────────────────────────
if ($subCommand === 'ensure-storage-dirs') {
    $root = dirname(dirname(__DIR__));
    ensureStorageDirs($root);
    exit(0);
}

// ── Route: run-as-owner ───────────────────────────────────────────────────
if ($subCommand === 'run-as-owner') {
    runAsOwner(array_slice($argv, 2));
    // never returns
}

// ── Guard 1: explicit installer mode env var ──────────────────────────────
if (getenv('WEBKERNEL_INSTALLER_MODE') === '1') {
    echo '[installer-guard] Skipping artisan call (WEBKERNEL_INSTALLER_MODE=1)' . PHP_EOL;
    exit(0);
}

// ── Guard 2: bootstrap/app.php not yet present ────────────────────────────
$bootstrapApp = dirname(__DIR__) . '/app.php';
if (!file_exists($bootstrapApp)) {
    echo '[installer-guard] Skipping artisan call (bootstrap/app.php not found)' . PHP_EOL;
    exit(0);
}

// ── Guard 3: .env not yet present ─────────────────────────────────────────
$envFile = dirname(dirname(__DIR__)) . '/.env';
if (!file_exists($envFile)) {
    echo '[installer-guard] Skipping artisan call (.env not yet present)' . PHP_EOL;
    exit(0);
}

// ── All guards passed: run the artisan command ────────────────────────────
$artisan = dirname(dirname(__DIR__)) . '/artisan';
$phpBin  = PHP_BINARY ?: 'php';
$command = $subCommand ?: 'package:discover';
$extra   = array_slice($argv, 2);
$args    = array_map('escapeshellarg', $extra);

$cmd = escapeshellarg($phpBin)
    . ' ' . escapeshellarg($artisan)
    . ' ' . escapeshellarg($command)
    . (empty($args) ? '' : ' ' . implode(' ', $args))
    . ' --ansi';

// proc_open replacement for passthru
$descriptorSpec = [
    0 => ["pipe", "r"],
    1 => ["pipe", "w"],
    2 => ["pipe", "w"],
];

$process = proc_open($cmd, $descriptorSpec, $pipes);

if (!is_resource($process)) {
    echo '[installer-guard] ERROR: Unable to start process.' . PHP_EOL;
    $exitCode = 1;
} else {
    fclose($pipes[0]);

    // stream output live (passthru-like)
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    while (true) {
        $status = proc_get_status($process);

        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);

        if ($out !== '') {
            echo $out;
        }

        if ($err !== '') {
            fwrite(STDERR, $err);
        }

        if (!$status['running']) {
            break;
        }

        usleep(100000); // 100ms
    }

    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);
}

exit($exitCode);

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Ensure all required Laravel and Webkernel storage directories exist.
 *
 * Called automatically by fixPermissions() before chmod/chown so that a fresh
 * clone or deploy never fails with "No such file or directory".
 *
 * Directories created with mode 02775 (rwxrwsr-x) so that new files written
 * by www-data inherit the project group automatically.
 *
 * @param string $root  Absolute path to the project root.
 */
function ensureStorageDirs(string $root): void
{
    $required = [
        // Laravel framework cache
        $root . '/storage/framework/cache/data',
        $root . '/storage/framework/sessions',
        $root . '/storage/framework/testing',
        $root . '/storage/framework/views',
        // Laravel logs
        $root . '/storage/logs',
        // Laravel app storage
        $root . '/storage/app/public',
        $root . '/storage/app/private',
        // Webkernel manifest cache
        $root . '/storage/webkernel/cache',
        // Bootstrap cache
        $root . '/bootstrap/cache',
    ];

    foreach ($required as $dir) {
        if (is_dir($dir)) {
            continue;
        }

        if (!@mkdir($dir, 0o2775, true)) {
            echo '[ensure-storage-dirs] WARN: Could not create ' . $dir . PHP_EOL;
            continue;
        }

        echo '[ensure-storage-dirs] Created: ' . $dir . PHP_EOL;
    }
}

/**
 * Fix ownership and permissions using PHP built-ins only — zero sudo/shell.
 *
 * Must run as root (or as the project owner for chmod-only).
 *
 * Ensures all required storage directories exist before applying permissions,
 * so this command is safe to run on a fresh clone.
 *
 * For each file/dir under the target paths:
 *   chown → project_user (uid)
 *   chgrp → www-data     (gid)
 *   chmod → dirs: 02775 (rwxrwsr-x), files: 0664 (rw-rw-r--)
 */
function fixPermissions(): void
{
    requirePosix();

    $projectUser = resolveProjectOwner();
    $ownerUid    = resolveUid($projectUser);
    $wwwGid      = resolveGid('www-data');
    $isRoot      = (posix_geteuid() === 0);

    echo '[fix-permissions] Project owner : ' . $projectUser . ' (uid=' . $ownerUid . ')' . PHP_EOL;
    echo '[fix-permissions] Group         : www-data (gid=' . $wwwGid . ')' . PHP_EOL;
    echo '[fix-permissions] Running as    : ' . ($isRoot ? 'root' : $projectUser) . PHP_EOL;

    $root = dirname(dirname(__DIR__));

    // Guarantee all directories exist before attempting to chmod/chown them.
    ensureStorageDirs($root);

    $targets = [
        $root . '/storage',
        $root . '/bootstrap/cache',
        $root . '/vendor',
    ];

    $errors = 0;

    foreach ($targets as $path) {
        if (!file_exists($path)) {
            echo '[fix-permissions] Skipping (not found): ' . $path . PHP_EOL;
            continue;
        }

        echo '[fix-permissions] Processing: ' . $path . PHP_EOL;
        $errors += applyRecursive($path, $ownerUid, $wwwGid, $isRoot);
    }

    if ($errors > 0) {
        echo '[fix-permissions] Done with ' . $errors . ' error(s).' . PHP_EOL;
        exit(1);
    }

    echo '[fix-permissions] Done — all permissions applied.' . PHP_EOL;
}

/**
 * Recursively apply chown/chgrp/chmod to a path using PHP built-ins.
 *
 * Dirs  → 02775  (rwxrwsr-x) — setgid bit so new files inherit www-data group
 * Files → 0664   (rw-rw-r--)
 *
 * @return int  number of errors encountered
 */
function applyRecursive(string $path, int $ownerUid, int $wwwGid, bool $canChown): int
{
    $errors = 0;

    $errors += applyToEntry($path, $ownerUid, $wwwGid, $canChown);

    if (!is_dir($path)) {
        return $errors;
    }

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($it as $entry) {
        $errors += applyToEntry((string) $entry->getRealPath(), $ownerUid, $wwwGid, $canChown);
    }

    return $errors;
}

/**
 * Apply chown/chgrp/chmod to a single file or directory.
 *
 * @return int  1 on any error, 0 on success
 */
function applyToEntry(string $path, int $ownerUid, int $wwwGid, bool $canChown): int
{
    $isDir = is_dir($path);
    $mode  = $isDir ? 0o2775 : 0o0664;
    $ok    = true;

    if ($canChown) {
        if (!@chown($path, $ownerUid)) {
            echo '[fix-permissions] WARN: chown failed on ' . $path . PHP_EOL;
            $ok = false;
        }

        if (!@chgrp($path, $wwwGid)) {
            echo '[fix-permissions] WARN: chgrp failed on ' . $path . PHP_EOL;
            $ok = false;
        }
    }

    if (!@chmod($path, $mode)) {
        echo '[fix-permissions] WARN: chmod failed on ' . $path . PHP_EOL;
        $ok = false;
    }

    return $ok ? 0 : 1;
}

/**
 * Re-execute a command as the project owner.
 *
 * If already the project owner → run directly via proc_open.
 * If root → drop privileges with posix_setuid/setgid before exec.
 *
 * Patches HOME, USER, and COMPOSER_HOME in the child environment so that
 * Composer resolves paths against the project owner's home directory, not root.
 *
 * @param string[] $cmdArgs  e.g. ['composer', 'w-clear']
 */
function runAsOwner(array $cmdArgs): void
{
    requirePosix();

    if (empty($cmdArgs)) {
        echo '[run-as-owner] ERROR: No command supplied.' . PHP_EOL;
        exit(1);
    }

    $projectUser = resolveProjectOwner();
    $ownerUid    = resolveUid($projectUser);
    $ownerInfo   = posix_getpwuid($ownerUid);
    $ownerGid    = $ownerInfo['gid'];
    $homeDir     = $ownerInfo['dir'];
    $currentUid  = posix_geteuid();

    if ($currentUid === $ownerUid) {
        echo '[run-as-owner] Already running as ' . $projectUser . ' — running directly.' . PHP_EOL;
    } elseif ($currentUid === 0) {
        echo '[run-as-owner] Dropping root → switching to ' . $projectUser . ' (uid=' . $ownerUid . ').' . PHP_EOL;

        if (!posix_setgid($ownerGid) || !posix_setuid($ownerUid)) {
            echo '[run-as-owner] ERROR: Could not drop privileges.' . PHP_EOL;
            exit(1);
        }
    } else {
        echo '[run-as-owner] ERROR: Cannot switch from current user to ' . $projectUser . ' without root.' . PHP_EOL;
        exit(1);
    }

    // Ensure child process inherits the correct identity context,
    // not root's HOME or COMPOSER_HOME.
    putenv('HOME=' . $homeDir);
    putenv('USER=' . $projectUser);
    putenv('COMPOSER_HOME=' . $homeDir . '/.composer');

    $label = implode(' ', $cmdArgs);
    echo '[run-as-owner] Exec: ' . $label . PHP_EOL;

    $proc = proc_open($cmdArgs, [STDIN, STDOUT, STDERR], $pipes);

    if ($proc === false) {
        echo '[run-as-owner] ERROR: Failed to start process.' . PHP_EOL;
        exit(1);
    }

    exit(proc_close($proc));
}

/**
 * Resolve the project owner name from the owner of bootstrap/app.php.
 */
function resolveProjectOwner(): string
{
    requirePosix();

    $appPhp = dirname(__DIR__) . '/app.php';

    if (!file_exists($appPhp)) {
        echo '[installer-guard] ERROR: bootstrap/app.php not found.' . PHP_EOL;
        exit(1);
    }

    $stat = stat($appPhp);

    if ($stat === false) {
        echo '[installer-guard] ERROR: stat() failed on bootstrap/app.php.' . PHP_EOL;
        exit(1);
    }

    $info = posix_getpwuid($stat['uid']);

    if ($info === false) {
        echo '[installer-guard] ERROR: Could not resolve owner UID ' . $stat['uid'] . '.' . PHP_EOL;
        exit(1);
    }

    return $info['name'];
}

/**
 * Resolve a username to its UID.
 */
function resolveUid(string $username): int
{
    $info = posix_getpwnam($username);

    if ($info === false) {
        echo '[installer-guard] ERROR: User not found: ' . $username . PHP_EOL;
        exit(1);
    }

    return $info['uid'];
}

/**
 * Resolve a group name to its GID.
 */
function resolveGid(string $group): int
{
    $info = posix_getgrnam($group);

    if ($info === false) {
        echo '[installer-guard] ERROR: Group not found: ' . $group . PHP_EOL;
        exit(1);
    }

    return $info['gid'];
}

/**
 * Abort if POSIX functions are unavailable.
 */
function requirePosix(): void
{
    if (!function_exists('posix_getpwuid') || !function_exists('posix_geteuid')) {
        echo '[installer-guard] ERROR: POSIX extension not available.' . PHP_EOL;
        exit(1);
    }
}
