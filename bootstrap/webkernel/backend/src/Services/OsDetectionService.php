<?php declare(strict_types=1);

namespace Webkernel\System\Services;

use Webkernel\System\Dto\OsInfo;
use Webkernel\System\Enums\OsFamily;
use Webkernel\System\Support\StaticDataCache;

/**
 * Detects and caches the host OS identity for the worker lifetime.
 *
 * All reads happen once on first call to detect(); subsequent calls
 * return the memoised OsInfo DTO — safe under Octane singletons.
 *
 * No shell_exec, no exec(). Reads only:
 *   PHP_OS_FAMILY, PHP_OS, php_uname(), /etc/os-release, /proc/sys/kernel/osrelease
 */
final class OsDetectionService
{
    public function detect(): OsInfo
    {
        return StaticDataCache::remember('os.info', fn() => $this->build());
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function build(): OsInfo
    {
        $family = OsFamily::current();

        return new OsInfo(
            family:        $family,
            distroName:    $this->resolveDistroName($family),
            kernelRelease: $this->resolveKernelRelease(),
            architecture:  php_uname('m'),
            hostname:      php_uname('n'),
            hasProcFs:     $family->isLinux() && @is_dir('/proc') && @is_readable('/proc'),
            ffiAvailable:  $this->resolveFfiAvailable(),
        );
    }

    private function resolveDistroName(OsFamily $family): string
    {
        if ($family->isLinux()) {
            $name = $this->parseOsRelease('PRETTY_NAME');
            if ($name !== '') {
                return $name;
            }
        }

        if ($family === OsFamily::Darwin) {
            return 'macOS ' . php_uname('r');
        }

        return PHP_OS;
    }

    private function resolveKernelRelease(): string
    {
        // Prefer /proc/sys/kernel/osrelease (Linux) — most reliable
        if (@is_readable('/proc/sys/kernel/osrelease')) {
            $v = trim((string) (@file_get_contents('/proc/sys/kernel/osrelease') ?: ''));
            if ($v !== '') {
                return $v;
            }
        }

        return php_uname('r');
    }

    /**
     * Parse a key from /etc/os-release.
     * Returns empty string when not found or not on Linux.
     */
    private function parseOsRelease(string $key): string
    {
        if (! @is_readable('/etc/os-release')) {
            return '';
        }

        $contents = (string) (@file_get_contents('/etc/os-release') ?: '');

        if (preg_match('/^' . preg_quote($key, '/') . '="?([^"\n]+)"?/m', $contents, $m)) {
            return trim($m[1]);
        }

        return '';
    }

    /**
     * FFI is "available" only when the extension is loaded AND
     * ffi.enable is set to true (not preload — preload only exposes
     * definitions loaded at startup, not dynamic runtime reads).
     */
    private function resolveFfiAvailable(): bool
    {
        if (! extension_loaded('ffi')) {
            return false;
        }

        $setting = strtolower(trim((string) ini_get('ffi.enable')));

        return in_array($setting, ['true', '1', 'on', 'yes'], true);
    }
}
