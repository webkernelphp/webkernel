<?php declare(strict_types=1);

namespace Webkernel\System\Contracts\Info;

/**
 * PHP build identity and runtime configuration.
 *
 * @api
 */
interface PhpInfoInterface
{
    /** Full PHP version string, e.g. "8.4.3". */
    public function version(): string;

    /** PHP_SAPI value, e.g. "fpm-fcgi", "cli". */
    public function sapi(): string;

    /**
     * Path to the loaded php.ini file.
     * Returns null when php_ini_loaded_file() returns false.
     */
    public function iniFile(): ?string;

    /** Number of currently loaded extensions. */
    public function extensionCount(): int;

    /**
     * Whether a specific extension is loaded.
     *
     * @param string $name  Extension name, e.g. "ffi", "sodium".
     */
    public function hasExtension(string $name): bool;
}
