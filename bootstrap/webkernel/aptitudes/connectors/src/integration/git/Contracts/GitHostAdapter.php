<?php declare(strict_types=1);

namespace Webkernel\Integration\Git\Contracts;

use Webkernel\Registry\Source;

/**
 * Contract for all git-hosting adapters (GitHub, GitLab, Gitea, Bitbucket …).
 */
interface GitHostAdapter
{
    public function supports(Source $source): bool;

    /** @return array<int, array<string, mixed>> */
    public function releases(Source $source, bool $includePreReleases = false): array;

    /**
     * @param array<string, mixed> $release
     * @throws \Webkernel\Integration\Git\Exceptions\NetworkException
     * @throws \Webkernel\Integration\Git\Exceptions\IntegrityException
     */
    public function download(array $release, string $targetDir): void;

    /**
     * @param array<string, mixed> $release
     * @throws \Webkernel\Integration\Git\Exceptions\IntegrityException
     */
    public function verify(string $content, array $release): bool;

    public function token(Source $source): ?string;

    public function withToken(string $token): static;
}
