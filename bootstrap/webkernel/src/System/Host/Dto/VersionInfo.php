<?php declare(strict_types=1);

namespace Webkernel\System\Host\Dto;

/**
 * Immutable representation of a single Webkernel release.
 */
final readonly class VersionInfo
{
    public function __construct(
        /** Semver string, e.g. "2.1.4" */
        public readonly string  $version,
        /** Release date as ISO-8601 string, e.g. "2025-11-03" — null when unknown. */
        public readonly ?string $releasedAt,
        /** True when this is the latest stable release. */
        public readonly bool    $isLatest,
        /** True when this is an LTS release. */
        public readonly bool    $isLts,
        /** True when this release receives security fixes only (EOL approaching). */
        public readonly bool    $securityOnly,
        /** URL to changelog, null when not available. */
        public readonly ?string $changelogUrl,
    ) {}

    /**
     * Semantic version parts.
     *
     * @return array{major: int, minor: int, patch: int}
     */
    public function parts(): array
    {
        $parts = explode('.', $this->version);

        return [
            'major' => (int) ($parts[0] ?? 0),
            'minor' => (int) ($parts[1] ?? 0),
            'patch' => (int) ($parts[2] ?? 0),
        ];
    }

    public function major(): int
    {
        return $this->parts()['major'];
    }

    public function minor(): int
    {
        return $this->parts()['minor'];
    }

    public function patch(): int
    {
        return $this->parts()['patch'];
    }

    /**
     * Returns true when $other is a newer version than this one.
     */
    public function isOlderThan(string $other): bool
    {
        return version_compare($this->version, $other, '<');
    }

    /**
     * Returns true when a newer release exists.
     */
    public function hasUpdate(string $latestVersion): bool
    {
        return $this->isOlderThan($latestVersion);
    }
}
