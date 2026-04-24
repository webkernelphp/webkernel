<?php declare(strict_types=1);

namespace Webkernel\System\Host\Managers;

use Webkernel\System\Host\Contracts\Managers\VersionManagerInterface;
use Webkernel\System\Host\Dto\VersionInfo;
use Webkernel\WebApp;

/**
 * Webkernel version and release manager.
 *
 * Current version: read from WebApp::webkernelVersion() which is set at
 * bootstrap time via WebApp::configure($basePath, version: '2.x.y').
 *
 * Release feed: the Webkernel releases API (configurable via
 * config('webkernel-system.webkernel_releases_api')).
 * Results are cached in memory for the worker lifetime — no disk I/O on
 * repeated calls.
 *
 * Bound as singleton() — version does not change per request.
 */
final class VersionManager implements VersionManagerInterface
{
    /** @var VersionInfo[]|null */
    private ?array $cachedReleases = null;

    private ?VersionInfo $cachedLatest = null;

    public function __construct(private readonly WebApp $app) {}

    // ── Interface ─────────────────────────────────────────────────────────────

    public function current(): VersionInfo
    {
        return new VersionInfo(
            version:      $this->currentString(),
            releasedAt:   null,
            isLatest:     ! $this->hasUpdate(),
            isLts:        false,
            securityOnly: false,
            changelogUrl: null,
        );
    }

    public function latest(): VersionInfo
    {
        if ($this->cachedLatest !== null) {
            return $this->cachedLatest;
        }

        $releases = $this->releases();

        if (! empty($releases)) {
            return $this->cachedLatest = $releases[0];
        }

        // Feed unavailable — latest = current
        return $this->cachedLatest = $this->current();
    }

    /**
     * @return VersionInfo[]
     */
    public function releases(): array
    {
        return $this->cachedReleases ??= $this->fetchReleases();
    }

    public function hasUpdate(): bool
    {
        $latest = $this->latestString();

        return version_compare($this->currentString(), $latest, '<');
    }

    public function currentString(): string
    {
        return $this->app->webkernelVersion();
    }

    public function latestString(): string
    {
        $releases = $this->releases();

        return ! empty($releases) ? $releases[0]->version : $this->currentString();
    }

    // ── Private ───────────────────────────────────────────────────────────────

    /**
     * Fetch releases from the configured API endpoint.
     * Fails silently — returns empty array on any error so the widget
     * still renders with current() data only.
     *
     * @return VersionInfo[]
     */
    private function fetchReleases(): array
    {
        $url = config('webkernel-system.webkernel_releases_api');

        if (! is_string($url) || $url === '') {
            return [];
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)->get($url);

            if (! $response->successful()) {
                return [];
            }

            /** @var array<array<string,mixed>> $data */
            $data = $response->json() ?? [];

            return $this->parseReleases($data);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Parse the releases API response into VersionInfo DTOs.
     *
     * Expected payload shape (array of release objects):
     * [
     *   { "version": "2.1.4", "released_at": "2025-11-03", "is_lts": false,
     *     "security_only": false, "changelog_url": "https://..." },
     *   ...
     * ]
     *
     * @param  array<array<string,mixed>>  $data
     * @return VersionInfo[]
     */
    private function parseReleases(array $data): array
    {
        $releases = [];

        foreach ($data as $item) {
            $version = $item['version'] ?? null;

            if (! is_string($version) || $version === '') {
                continue;
            }

            $releases[] = new VersionInfo(
                version:      $version,
                releasedAt:   is_string($item['released_at'] ?? null) ? $item['released_at'] : null,
                isLatest:     (bool) ($item['is_latest'] ?? false),
                isLts:        (bool) ($item['is_lts'] ?? false),
                securityOnly: (bool) ($item['security_only'] ?? false),
                changelogUrl: is_string($item['changelog_url'] ?? null) ? $item['changelog_url'] : null,
            );
        }

        // Sort newest first
        usort($releases, static fn(VersionInfo $a, VersionInfo $b)
            => version_compare($b->version, $a->version));

        return $releases;
    }
}
