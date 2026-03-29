<?php declare(strict_types=1);

namespace Webkernel\Http\Git\Contracts;

use Webkernel\Registry\Source;

/**
 * Contract for all git-hosting adapters (GitHub, GitLab, Bitbucket, Gitea …).
 *
 * Every method is pure in the sense that it does not mutate state beyond
 * transient HTTP calls. The Token store is injected by the adapter's
 * constructor; callers do not manage credentials.
 */
interface GitHostAdapter
{
    /**
     * Whether this adapter can handle the given Source.
     */
    public function supports(Source $source): bool;

    /**
     * Fetch the list of published releases for a Source.
     * Returns an empty array when no releases exist.
     *
     * @return array<int, array<string, mixed>>
     * @throws \Webkernel\Http\Git\Exceptions\NetworkException
     */
    public function releases(Source $source, bool $includePreReleases = false): array;

    /**
     * Download a specific release archive into $targetDir.
     * The directory will be created if it does not exist.
     *
     * @param array<string, mixed> $release  One entry from releases()
     * @throws \Webkernel\Http\Git\Exceptions\NetworkException
     * @throws \Webkernel\Http\Git\Exceptions\IntegrityException
     */
    public function download(array $release, string $targetDir): void;

    /**
     * Verify the SHA-256 checksum of downloaded content against release metadata.
     * Returns true when the release carries no checksum (no-op).
     *
     * @param array<string, mixed> $release
     * @throws \Webkernel\Http\Git\Exceptions\IntegrityException
     */
    public function verify(string $content, array $release): bool;

    /**
     * Return the resolved token for this source, or null if none is stored.
     */
    public function token(Source $source): ?string;

    /**
     * Return a clone of this adapter with the given token injected explicitly,
     * bypassing the token store.
     */
    public function withToken(string $token): static;
}
