<?php declare(strict_types=1);

namespace Webkernel\System\Ops\Contracts;

/**
 * Extended source provider that enriches releases with metadata.
 *
 * Metadata includes: codename, features, doc_links, video_url, notes.
 * Implementations must also implement SourceProvider.
 */
interface SourceProviderWithMetadata extends SourceProvider
{
    /**
     * Fetch releases with metadata from annotated tags or release bodies.
     *
     * @return array<int, array<string, mixed>>  Release payloads with 'metadata' key
     */
    public function releasesWithMetadata(): array;
}
