#!/usr/bin/env php
<?php declare(strict_types=1);
/**
 * Webkernel™ release tool
 *
 * Usage (from project root):
 *   php bootstrap/webkernel/Makefile.php keygen
 *   php bootstrap/webkernel/Makefile.php release --key=X [--no-keep]
 *   php bootstrap/webkernel/Makefile.php patch   --key=X [--no-keep]
 *   php bootstrap/webkernel/Makefile.php minor   --key=X [--no-keep]
 *   php bootstrap/webkernel/Makefile.php major   --key=X [--no-keep]
 *   php bootstrap/webkernel/Makefile.php info    --key=X
 *
 * Requires bootstrap/ to be a git repo tracking:
 *   https://github.com/webkernelphp/foundation
 */
use function Laravel\Prompts\{error, info, warning, note, confirm, text, select, intro, outro, spin};
use Webkernel\Integration\Git\Local\GitRunner;
use Webkernel\Process;
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/fast-boot.php';
const BOOT            = __DIR__ . '/fast-boot.php';
const BUILD_FILE      = __DIR__ . '/support/.build-number';
const COMPOSER_JSON   = __DIR__ . '/../composer.json';
const COMPOSER_LOCK   = __DIR__ . '/../../composer.lock';
const DEV_TOOLS       = __DIR__ . '/../../dev-tools.php';
const KEY_CACHE       = __DIR__ . '/../../storage/webkernel/cache/.build-token';
const KEY_TTL         = 1800;
const REQUIRED_REMOTE = 'https://github.com/webkernelphp/foundation';
const BOOTSTRAP_DIR   = __DIR__ . '/..';
// ── Git — all calls route through GitRunner ───────────────────────────────────
function gitRunner(): GitRunner
{
    static $runner = null;
    return $runner ??= new GitRunner(BOOTSTRAP_DIR);
}
function gitCommitShort(): string { return gitRunner()->revParseShort()->value(); }
function gitCommitFull(): string  { return gitRunner()->revParseFull()->value(); }
function gitBranch(): string      { return gitRunner()->currentBranch(); }
function gitTag(): string         { return gitRunner()->currentTag(); }
function hasSigningConfig(): bool { return gitRunner()->hasSigning(); }
// ── Argument parsing ──────────────────────────────────────────────────────────
function parseArgs(array $argv): array
{
    $key  = '';
    $keep = true;
    foreach ($argv as $arg) {
        if (str_starts_with($arg, '--key=')) { $key  = substr($arg, 6); }
        if ($arg === '--no-keep')            { $keep = false; }
    }
    return compact('key', 'keep');
}
// ── Guards ────────────────────────────────────────────────────────────────────
function assertFoundationRepo(): void
{
    $runner = gitRunner();
    if (!$runner->isRepo()) {
        error('bootstrap/ is not a git repository.');
        note(implode("\n", [
            'This tool requires bootstrap/ to track the official foundation:',
            '',
            '  ' . REQUIRED_REMOTE,
            '',
            '  cd bootstrap && git init',
            '  git remote add origin ' . REQUIRED_REMOTE,
        ]));
        exit(1);
    }
    $normalize = static fn(string $u): string => rtrim(preg_replace('/\.git$/', '', $u), '/');
    $remote    = $runner->remoteGetUrl('origin')->value('');
    if ($normalize($remote) !== $normalize(REQUIRED_REMOTE)) {
        error('bootstrap/ remote does not point to the Webkernel Foundation repository.');
        note(implode("\n", [
            'Found:    ' . ($remote ?: '(none)'),
            'Required: ' . REQUIRED_REMOTE,
            '',
            'This tool is not supported on unofficial forks or mirrors.',
        ]));
        exit(1);
    }
}
function assertDevMode(): void
{
    if (!is_file(DEV_TOOLS)) {
        error('dev-tools.php not found. This tool only runs in dev mode.');
        exit(1);
    }
    $config = require DEV_TOOLS;
    if (!is_array($config) || ($config['dev-mode'] ?? false) !== true) {
        error('dev-mode is not enabled in dev-tools.php.');
        exit(1);
    }
}
function legalWarning(): void
{
    warning('INTEGRITY NOTICE — READ BEFORE PROCEEDING');
    note(implode("\n", [
        'This operation modifies bootstrap/webkernel/fast-boot.php,',
        'part of the Webkernel Core bootstrap directory.',
        '',
        'Under the Webkernel Unified License (v1.2), any modification',
        'to the bootstrap directory:',
        '',
        '  • Voids eligibility for Official Recognition and certification',
        '  • Removes entitlement to support from Numerimondes',
        '  • Invalidates the Core integrity chain for this instance',
        '  • May affect module activation and remote verification',
        '',
        'Authorized exclusively for use by the Core developer (Numerimondes)',
        'during official development builds.',
        '',
        'Numerimondes — https://webkernelphp.com/',
    ]));
}
// ── Key management ────────────────────────────────────────────────────────────
function keygen(): void
{
    assertFoundationRepo();
    assertDevMode();
    intro('Webkernel™ — Key Generator');
    legalWarning();
    if (!confirm('I confirm this is a development build, not a certified production instance', default: false)) {
        warning('Aborted.');
        exit(0);
    }
    $key       = bin2hex(openssl_random_pseudo_bytes(32));
    $expiresAt = time() + KEY_TTL;
    $dir = dirname(KEY_CACHE);
    if (!is_dir($dir)) { mkdir($dir, 0750, true); }
    file_put_contents(KEY_CACHE, json_encode(['key' => $key, 'expires_at' => $expiresAt]), LOCK_EX);
    chmod(KEY_CACHE, 0600);
    outro('Key generated — expires in ' . (KEY_TTL / 60) . ' minutes · keep is default');
    info("Key: {$key}");
    note(implode("\n", [
        '  php bootstrap/webkernel/Makefile.php patch   --key=' . $key,
        '  php bootstrap/webkernel/Makefile.php release --key=' . $key,
        '',
        '  Add --no-keep to invalidate after first use.',
    ]));
}
function validateKey(string $provided, bool $keep): void
{
    if (!is_file(KEY_CACHE)) {
        error('No active key. Run: php bootstrap/webkernel/Makefile.php keygen');
        exit(1);
    }
    $payload = json_decode(file_get_contents(KEY_CACHE), true);
    if (!is_array($payload) || !isset($payload['key'], $payload['expires_at'])) {
        error('Corrupted key cache. Run keygen again.');
        exit(1);
    }
    if (time() > $payload['expires_at']) {
        unlink(KEY_CACHE);
        error('Key expired. Run: php bootstrap/webkernel/Makefile.php keygen');
        exit(1);
    }
    if (!hash_equals($payload['key'], $provided)) {
        error('Invalid key.');
        exit(1);
    }
    if ($keep) {
        $remaining = $payload['expires_at'] - time();
        info("Key kept — expires in {$remaining}s");
    } else {
        unlink(KEY_CACHE);
        info('Key invalidated (single use).');
    }
}
function guard(array $argv): void
{
    assertFoundationRepo();
    assertDevMode();
    $args = parseArgs($argv);
    if ($args['key'] === '') {
        error('Missing --key. Run keygen first.');
        note('  php bootstrap/webkernel/Makefile.php keygen');
        exit(1);
    }
    validateKey($args['key'], $args['keep']);
}
// ── Composer helpers ──────────────────────────────────────────────────────────
function readLock(): array
{
    static $lock = null;
    if ($lock === null) {
        $lock = is_file(COMPOSER_LOCK)
            ? (json_decode(file_get_contents(COMPOSER_LOCK), true) ?? [])
            : [];
    }
    return $lock;
}
function readComposerJson(): array
{
    return is_file(COMPOSER_JSON)
        ? (json_decode(file_get_contents(COMPOSER_JSON), true) ?? [])
        : [];
}
function lockedVersion(string $package): ?string
{
    foreach (array_merge(readLock()['packages'] ?? [], readLock()['packages-dev'] ?? []) as $p) {
        if (($p['name'] ?? '') === $package) { return ltrim($p['version'] ?? '', 'v'); }
    }
    return null;
}
function phpConstraintOf(string $package): ?string
{
    foreach (array_merge(readLock()['packages'] ?? [], readLock()['packages-dev'] ?? []) as $p) {
        if (($p['name'] ?? '') === $package) { return $p['require']['php'] ?? null; }
    }
    return null;
}
function minFromConstraint(string $constraint): string
{
    $first = trim(explode('|', $constraint)[0]);
    $clean = preg_replace('/[^0-9.]/', '', $first);
    if ($clean === '' || $clean === '.') { return '0.0.0'; }
    $parts = array_slice(array_pad(explode('.', $clean), 3, '0'), 0, 3);
    return implode('.', array_map('intval', $parts));
}
function resolvePhpMinimum(): string
{
    $candidates = [];
    foreach (['laravel/framework', 'filament/filament', 'livewire/livewire', 'livewire/blaze'] as $pkg) {
        $c = phpConstraintOf($pkg);
        if ($c !== null) {
            $min = minFromConstraint($c);
            if ($min !== '0.0.0') { $candidates[] = $min; }
        }
    }
    if (empty($candidates)) { return PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.0'; }
    usort($candidates, 'version_compare');
    return end($candidates);
}
function composerVersion(): string
{
    foreach (['composer', 'composer.phar'] as $bin) {
        $p = Process::fromArray([$bin, '--version', '--no-ansi']);
        $p->run();
        if ($p->isSuccessful()) {
            preg_match('/Composer version (\d+\.\d+\.\d+)/', $p->getOutput(), $m);
            if (isset($m[1])) { return $m[1]; }
        }
    }
    return 'unknown';
}
function buildRequires(): array
{
    return [
        'php'      => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION,
        'laravel'  => lockedVersion('laravel/framework') ?? 'unknown',
        'filament' => lockedVersion('filament/filament')  ?? 'unknown',
        'livewire' => lockedVersion('livewire/livewire') ?? lockedVersion('livewire/blaze') ?? 'unknown',
        'composer' => composerVersion(),
    ];
}
function buildCompatibleWith(): array
{
    $require = readComposerJson()['require'] ?? [];
    return [
        'php'      => resolvePhpMinimum(),
        'laravel'  => isset($require['laravel/framework'])
            ? minFromConstraint($require['laravel/framework']) : '12.0.0',
        'filament' => isset($require['filament/filament'])
            ? minFromConstraint($require['filament/filament']) : '3.3.0',
        'livewire' => isset($require['livewire/livewire'])
            ? minFromConstraint($require['livewire/livewire'])
            : (isset($require['livewire/blaze'])
                ? minFromConstraint($require['livewire/blaze']) : '3.5.0'),
        'composer' => '2.5.0',
    ];
}
// ── Read current values from fast-boot.php ────────────────────────────────────
function readVersion(): string
{
    preg_match("/define\('WEBKERNEL_VERSION',\s*'([^']+)'\)/", file_get_contents(BOOT), $m);
    return $m[1] ?? '0.0.0';
}
function readCodename(): string
{
    preg_match("/define\('WEBKERNEL_CODENAME',\s*'([^']+)'\)/", file_get_contents(BOOT), $m);
    return $m[1] ?? 'sovereign';
}
function readChannel(): string
{
    preg_match("/define\('WEBKERNEL_CHANNEL',\s*'([^']+)'\)/", file_get_contents(BOOT), $m);
    return $m[1] ?? 'stable';
}
function readBuild(): int
{
    return is_file(BUILD_FILE) ? (int) trim(file_get_contents(BUILD_FILE)) : 0;
}
function bumpVersion(string $version, string $part): string
{
    [$major, $minor, $patch] = array_map('intval', explode('.', $version));
    match ($part) {
        'major' => [$major, $minor, $patch] = [$major + 1, 0, 0],
        'minor' => [$major, $minor, $patch] = [$major, $minor + 1, 0],
        'patch' => [$major, $minor, $patch] = [$major, $minor, $patch + 1],
    };
    return "{$major}.{$minor}.{$patch}";
}
function arrayToPhpLiteral(array $data, int $indent = 4): string
{
    $pad = str_repeat(' ', $indent);
    $lines = [];
    foreach ($data as $k => $v) {
        $lines[] = "{$pad}'{$k}'" . str_pad('', max(1, 10 - strlen($k))) . "=> '{$v}',";
    }
    return "[\n" . implode("\n", $lines) . "\n]";
}
function stamp(string $version, int $build, string $codename, string $channel): void
{
    // git calls happen BEFORE stamping so commit hash reflects the state before this release
    $commitShort = gitCommitShort();
    $commitFull  = gitCommitFull();
    $branch      = gitBranch();
    $tag         = gitTag() ?: "v{$version}";
    $requires    = buildRequires();
    $compat      = buildCompatibleWith();
    $scalars = [
        'WEBKERNEL_VERSION'     => "'{$version}'",
        'WEBKERNEL_BUILD'       => (string) $build,
        'WEBKERNEL_SEMVER'      => "'" . $version . '+' . $build . "'",
        'WEBKERNEL_CODENAME'    => "'{$codename}'",
        'WEBKERNEL_CHANNEL'     => "'{$channel}'",
        'WEBKERNEL_RELEASED_AT' => "'" . date('Y-m-d') . "'",
        'WEBKERNEL_COMMIT'      => "'{$commitShort}'",
        'WEBKERNEL_COMMIT_FULL' => "'{$commitFull}'",
        'WEBKERNEL_BRANCH'      => "'{$branch}'",
        'WEBKERNEL_TAG'         => "'{$tag}'",
    ];
    $content = file_get_contents(BOOT);
    foreach ($scalars as $constant => $value) {
        $content = preg_replace(
            "/(?<=define\('{$constant}',\s{0,10})[^)]+(?=\))/",
            $value,
            $content
        );
    }
    foreach ([
        'WEBKERNEL_REQUIRES'        => $requires,
        'WEBKERNEL_COMPATIBLE_WITH' => $compat,
    ] as $constant => $array) {
        $content = preg_replace(
            "/(?<=define\('{$constant}', )\\[.*?\\](?=\))/s",
            arrayToPhpLiteral($array),
            $content
        );
    }
    spin(function () use ($content, $build): void {
        file_put_contents(BOOT, $content);
        file_put_contents(BUILD_FILE, (string) $build);
    }, 'Stamping fast-boot.php…');
}
// ── Git commit + push whole bootstrap/ ───────────────────────────────────────
function gitCommitAndTag(string $semver, string $codename): void
{
    if (!confirm("Commit all changes in bootstrap/ and tag {$semver}?", default: true)) {
        warning('Skipped git commit. Remember to commit manually.');
        $signFlags = hasSigningConfig() ? '-S' : '';
        $tagSignFlags = hasSigningConfig() ? '-s' : '';
        note(implode("\n", [
            '  cd bootstrap',
            '  git add .',
            "  git commit {$signFlags} -m \"release: {$semver} ({$codename})\"",
            "  git tag {$tagSignFlags} {$semver}",
            '  git push origin HEAD',
            "  git push origin {$semver}",
        ]));
        return;
    }
    $commitMsg = text(
        label: 'Commit message',
        default: "release: {$semver} ({$codename})",
        required: true,
    );
    $committed = false;
    spin(function () use ($commitMsg, $semver, &$committed): void {
        $runner = gitRunner();
        $runner->add('.');
        $signed = $runner->hasSigning();
        $commit = $signed ? $runner->commitSigned($commitMsg) : $runner->commit($commitMsg);
        if (!$commit->ok) { return; }
        $committed = true;
        $signed ? $runner->tagSigned($semver) : $runner->tag($semver);
    }, "Committing bootstrap/ → {$semver}…");
    if (!$committed) {
        warning('Git commit failed — nothing to commit or an error occurred.');
        return;
    }
    info("Committed and tagged {$semver}");
    if (!confirm('Push to origin (' . REQUIRED_REMOTE . ')?', default: true)) {
        note(implode("\n", [
            'Push manually when ready:',
            '  cd bootstrap',
            '  git push origin HEAD',
            "  git push origin {$semver}",
        ]));
        return;
    }
    spin(function () use ($semver): void {
        $runner = gitRunner();
        $runner->push('origin', 'HEAD');
        $runner->push('origin', $semver);
    }, 'Pushing to origin…');
    info('Pushed.');
}
// ── Interactive release flow ──────────────────────────────────────────────────
function runStamp(string $version, int $build): void
{
    $semver = "{$version}+{$build}";
    intro("Webkernel™ — Release {$semver}");
    legalWarning();
    if (!confirm('I confirm this is a development build, not a certified production instance', default: false)) {
        warning('Aborted. No files were modified.');
        exit(0);
    }
    if (!hasSigningConfig()) {
        warning('No GPG/SSH signing key configured');
        note(implode("\n", [
            'Commits and tags will NOT be signed. To enable signing:',
            '',
            '  # For SSH signing (recommended):',
            '  git config --global gpg.format ssh',
            '  git config --global user.signingkey ~/.ssh/id_ed25519.pub',
            '',
            '  # For GPG signing:',
            '  git config --global user.signingkey YOUR_KEY_ID',
            '',
            'See: https://docs.github.com/en/authentication/managing-commit-signature-verification',
        ]));
    } else {
        info('Signing enabled — commits and tags will be signed');
    }
    $codename = text(
        label: 'Codename',
        default: readCodename(),
        hint: 'Press Enter to keep current codename.',
    );
    $channel = select(
        label: 'Release channel',
        options: ['stable', 'lts', 'rc', 'dev'],
        default: readChannel(),
    );
    if ($channel !== 'stable') {
        warning("Channel '{$channel}' — not eligible for Official Recognition or certification.");
    }
    $requires = buildRequires();
    $compat   = buildCompatibleWith();
    stamp($version, $build, $codename, $channel);
    outro("Released {$semver} · {$codename} · {$channel}");
    info('Build stamped into fast-boot.php');
    note(implode("\n", [
        'commit           ' . gitCommitShort() . '  (' . gitBranch() . ')',
        'date             ' . date('Y-m-d'),
        'codename         ' . $codename,
        'channel          ' . $channel,
        'requires         php ' . $requires['php']  . ' · laravel ' . $requires['laravel']  . ' · filament ' . $requires['filament'],
        'compatible_with  php ' . $compat['php']    . ' · laravel ' . $compat['laravel']    . ' · filament ' . $compat['filament'],
    ]));
    gitCommitAndTag($semver, $codename);
}
// ── Commands ──────────────────────────────────────────────────────────────────
$command = $argv[1] ?? 'help';
match ($command) {
    'keygen' => keygen(),
    'release' => (function () use ($argv): void {
        guard($argv);
        runStamp(readVersion(), readBuild() + 1);
    })(),
    'patch', 'minor', 'major' => (function () use ($command, $argv): void {
        guard($argv);
        runStamp(bumpVersion(readVersion(), $command), readBuild() + 1);
    })(),
    'info' => (function () use ($argv): void {
        guard($argv);
        $v      = readVersion();
        $b      = readBuild();
        $req    = buildRequires();
        $compat = buildCompatibleWith();
        intro('Webkernel™ — Release Info');
        note(implode("\n", [
            'version          ' . $v,
            'semver           ' . "{$v}+{$b}",
            'build            ' . $b,
            'codename         ' . readCodename(),
            'channel          ' . readChannel(),
            'commit           ' . gitCommitShort() . '  (' . gitBranch() . ')',
            '',
            'requires         php ' . $req['php']    . ' · laravel ' . $req['laravel']    . ' · filament ' . $req['filament']    . ' · livewire ' . $req['livewire']    . ' · composer ' . $req['composer'],
            'compatible_with  php ' . $compat['php'] . ' · laravel ' . $compat['laravel'] . ' · filament ' . $compat['filament'] . ' · livewire ' . $compat['livewire'],
        ]));
        outro('');
    })(),
    default => (function (): void {
        intro('Webkernel™ — Release Tool');
        note(implode("\n", [
            'keygen                        generate a 30-min key (keep is default)',
            'release --key=X [--no-keep]   bump build + interactive stamp + git',
            'patch   --key=X [--no-keep]   1.0.1 → 1.0.2',
            'minor   --key=X [--no-keep]   1.0.1 → 1.1.0',
            'major   --key=X [--no-keep]   1.0.1 → 2.0.0',
            'info    --key=X               show current release info',
            '',
            'Requires bootstrap/ to track:',
            '  ' . REQUIRED_REMOTE,
        ]));
        outro('');
    })(),
};
