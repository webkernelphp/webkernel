<?php declare(strict_types=1);

namespace Webkernel\System\Contracts\Managers;

use Webkernel\System\Dto\VersionInfo;

/**
 * Webkernel version and release information.
 *
 * @api
 *
 * Usage:
 *   webkernel()->version()              // "2.1.4"  (shorthand alias)
 *   webkernel()->versions()->current()  // VersionInfo for the running version
 *   webkernel()->versions()->latest()   // VersionInfo for the latest known release
 *   webkernel()->versions()->releases() // VersionInfo[] all known releases
 *   webkernel()->versions()->hasUpdate() // bool — is a newer version available?
 */
interface VersionManagerInterface
{
    /**
     * Currently installed Webkernel version string.
     * Equivalent to webkernel()->version().
     */
    public function current(): VersionInfo;

    /**
     * Latest known stable release.
     * Returns current() when no release feed is available.
     */
    public function latest(): VersionInfo;

    /**
     * All known releases, newest first.
     *
     * @return VersionInfo[]
     */
    public function releases(): array;

    /**
     * True when a newer stable release than the running version is available.
     */
    public function hasUpdate(): bool;

    /**
     * Raw version string of the currently running Webkernel.
     */
    public function currentString(): string;

    /**
     * Raw version string of the latest known stable release.
     */
    public function latestString(): string;
}
