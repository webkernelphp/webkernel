<?php declare(strict_types=1);

namespace Webkernel\System\Ops\Contracts;

/**
 * Source provider interface for fetching releases and artifacts.
 *
 * Implementations: GitHubProvider, GitLabProvider, HttpProvider
 */
interface SourceProvider
{
    /**
     * Fetch releases from the source.
     *
     * @return array<int, array<string, mixed>>  Release payloads
     */
    public function releases(): array;

    /**
     * Download release artifact to destination.
     *
     * @param string $version
     * @param string $destination
     * @return bool Success
     */
    public function download(string $version, string $destination): bool;
}
