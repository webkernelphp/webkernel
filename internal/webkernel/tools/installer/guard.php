<?php declare(strict_types=1);
/** @var WebkernelToolRunner $runner */
/** @var array<string>       $args   */
/** @phpstan-constant BASE_PATH string */

$sub = $args[0] ?? '';

match ($sub) {
    'init-gitkeeps' => (static function (): void {
        $dirs = ['node_modules', 'packages'];
        foreach ($dirs as $dir) {
            $path = BASE_PATH . '/' . $dir . '/.gitkeep';
            if (!file_exists($path)) {
                @mkdir(dirname($path), 0o2775, true);
                @touch($path);
                echo '[init-gitkeeps] Created: ' . $path . PHP_EOL;
            }
        }
    })(),
    'fix-permissions'     => (static function (): void { _guardFixPermissions(); })(),
    'ensure-storage-dirs' => (static function (): void { _guardEnsureStorageDirs(BASE_PATH); })(),
    'run-as-owner'        => (static function (array $a): void { _guardRunAsOwner(array_slice($a, 1)); })($args),
    default               => (static function (string $sub, array $args, WebkernelToolRunner $runner): void {

        if (getenv('WEBKERNEL_INSTALLER_MODE') === '1') {
            echo '[installer-guard] Skipped (WEBKERNEL_INSTALLER_MODE=1)' . PHP_EOL;
            return;
        }
        if (!file_exists(BASE_PATH . '/bootstrap/app.php')) {
            echo '[installer-guard] Skipped (bootstrap/app.php not found)' . PHP_EOL;
            return;
        }
        if (!file_exists(BASE_PATH . '/.env')) {
            echo '[installer-guard] Skipped (.env not found)' . PHP_EOL;
            return;
        }

        $cmd   = $sub !== '' ? $sub : 'package:discover';
        $extra = array_slice($args, 1);
        $runner->artisan($cmd, $extra);

    })($sub, $args, $runner),
};

// -- Helpers ------------------------------------------------------------------

function _guardEnsureStorageDirs(string $root): void
{
    $dirs = [
        $root . '/storage/framework/cache/data',
        $root . '/storage/framework/sessions',
        $root . '/storage/framework/testing',
        $root . '/storage/framework/views',
        $root . '/storage/logs',
        $root . '/storage/app/public',
        $root . '/storage/app/private',
        $root . '/storage/webkernel/cache',
        $root . '/bootstrap/cache',
    ];
    foreach ($dirs as $dir) {
        is_dir($dir) || @mkdir($dir, 0o2775, true)
            ? print('[ensure-storage-dirs] OK: ' . $dir . PHP_EOL)
            : fwrite(STDERR, '[ensure-storage-dirs] WARN: failed: ' . $dir . PHP_EOL);
    }
}

function _guardRequirePosix(): void
{
    if (!function_exists('posix_geteuid')) {
        fwrite(STDERR, '[installer-guard] POSIX extension not available.' . PHP_EOL);
        exit(1);
    }
}

function _guardOwnerOf(string $root): array
{
    _guardRequirePosix();
    $stat = stat($root . '/bootstrap/app.php');
    if ($stat === false) { fwrite(STDERR, '[installer-guard] stat() failed.' . PHP_EOL); exit(1); }
    $info = posix_getpwuid($stat['uid']);
    if ($info === false) { fwrite(STDERR, '[installer-guard] unknown UID ' . $stat['uid'] . PHP_EOL); exit(1); }
    return $info;
}

function _guardFixPermissions(): void
{
    _guardRequirePosix();

    $owner   = _guardOwnerOf(BASE_PATH);
    $uid     = (int) $owner['uid'];
    $gid     = (int) (posix_getgrnam('www-data')['gid'] ?? -1);
    $isRoot  = posix_geteuid() === 0;

    if ($gid === -1) { fwrite(STDERR, '[fix-permissions] www-data group not found.' . PHP_EOL); exit(1); }

    echo '[fix-permissions] Owner: ' . $owner['name'] . ' uid=' . $uid . ' | group: www-data gid=' . $gid . PHP_EOL;

    _guardEnsureStorageDirs(BASE_PATH);

    $errors = 0;
    foreach ([BASE_PATH . '/storage', BASE_PATH . '/bootstrap/cache', BASE_PATH . '/vendor'] as $path) {
        if (!file_exists($path)) continue;
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ([$path, ...$it] as $entry) {
            $p    = (string)$entry;
            $mode = is_dir($p) ? 0o2775 : 0o0664;
            if ($isRoot) { @chown($p, $uid) || ($errors++ && fwrite(STDERR, '[fix-permissions] chown failed: ' . $p . PHP_EOL)); }
            if ($isRoot) { @chgrp($p, $gid) || ($errors++ && fwrite(STDERR, '[fix-permissions] chgrp failed: ' . $p . PHP_EOL)); }
            @chmod($p, $mode) || ($errors++ && fwrite(STDERR, '[fix-permissions] chmod failed: ' . $p . PHP_EOL));
        }
    }
    echo '[fix-permissions] Done' . ($errors ? ' with ' . $errors . ' error(s).' : '.') . PHP_EOL;
    $errors && exit(1);
}

function _guardRunAsOwner(array $cmd): void
{
    _guardRequirePosix();

    if (empty($cmd)) { fwrite(STDERR, '[run-as-owner] No command supplied.' . PHP_EOL); exit(1); }

    $owner      = _guardOwnerOf(BASE_PATH);
    $uid        = (int) $owner['uid'];
    $gid        = (int) $owner['gid'];
    $currentUid = posix_geteuid();

    if ($currentUid !== $uid) {
        if ($currentUid !== 0) { fwrite(STDERR, '[run-as-owner] Need root to switch user.' . PHP_EOL); exit(1); }
        posix_setgid($gid) && posix_setuid($uid) || (fwrite(STDERR, '[run-as-owner] Cannot drop privileges.' . PHP_EOL) && exit(1));
    }

    putenv('HOME=' . $owner['dir']);
    putenv('USER=' . $owner['name']);
    putenv('COMPOSER_HOME=' . $owner['dir'] . '/.composer');

    echo '[run-as-owner] ' . implode(' ', $cmd) . PHP_EOL;

    $p = proc_open($cmd, [STDIN, STDOUT, STDERR], $pipes);
    exit(is_resource($p) ? proc_close($p) : 1);
}
