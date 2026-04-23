<?php declare(strict_types=1);

namespace Webkernel\System\Operations;

interface SourceProvider
{
    /**
     * Fetch all releases from the source.
     * Each release should include at minimum: tag_name, version, artifact_url.
     *
     * @return array<int, array<string, mixed>>
     */
    public function releases(): array;

    /**
     * Download an artifact to a target directory.
     */
    public function download(string $artifactUrl, string $targetDir): void;

    public function name(): string;
}

/**
 * Optional interface for sources that can fetch additional metadata.
 */
interface SourceProviderWithMetadata extends SourceProvider
{
    /**
     * Fetch metadata for a single release.
     *
     * @param array<string, mixed> $release
     * @return array<string, mixed>|null
     */
    public function metadata(array $release): ?array;
}
