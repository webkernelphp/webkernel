<?php declare(strict_types=1);

namespace Webkernel\Http\Git;

use Webkernel\Http\Git\Contracts\GitHostAdapter;
use Webkernel\Http\Git\Exceptions\NetworkException;
use Webkernel\Http\Git\HttpGitClient as HttpClient;
use Webkernel\Registry\Providers;
use Webkernel\Registry\Source;
use Webkernel\Registry\Token;

/**
 * GitLab adapter.
 *
 * Covers gitlab.com and self-hosted Gitea / Forgejo instances that speak the
 * GitLab API v4 dialect (pass a custom Source with the self-hosted base URL).
 *
 * Release listing uses the GitLab "Releases" REST endpoint.
 * When no releases exist, falls back to the repository's default branch.
 */
final class GitLabAdapter implements GitHostAdapter
{
    private ?string $explicitToken = null;

    public function __construct(private readonly Token $tokenStore) {}

    public function withToken(string $token): self
    {
        $clone                = clone $this;
        $clone->explicitToken = $token;
        return $clone;
    }

    // ── GitHostAdapter ────────────────────────────────────────────────────────

    public function supports(Source $source): bool
    {
        return $source->provider === Providers::GitLab
            || $source->provider === Providers::Numerimondes;
    }

    /**
     * @return array<int, array<string, mixed>>
     * @throws NetworkException
     */
    public function releases(Source $source, bool $includePreReleases = false): array
    {
        $client    = $this->client($source);
        $projectId = urlencode("{$source->vendor}/{$source->slug}");
        $apiBase   = $source->apiBase();
        $url       = "{$apiBase}/projects/{$projectId}/releases";

        $result = $client->getWithStatus($url);

        if ($result['status'] === 404) {
            return $this->branchFallback($client, $source);
        }

        if ($result['status'] !== 200) {
            throw new NetworkException(
                "GitLab releases endpoint returned HTTP {$result['status']} for [{$source}]."
            );
        }

        $data = json_decode($result['body'], true);

        if (!is_array($data) || empty($data)) {
            return $this->branchFallback($client, $source);
        }

        // Normalise to the same shape as GitHubAdapter so callers are provider-agnostic
        return array_values(array_map(static function (array $r) use ($source): array {
            $assets   = $r['assets'] ?? [];
            $sources  = $r['assets']['sources'] ?? [];
            $zipEntry = current(array_filter($sources, static fn ($s) => ($s['format'] ?? '') === 'zip'));
            return [
                'tag_name'    => $r['tag_name']   ?? $r['name'] ?? '',
                'name'        => $r['name']        ?? '',
                'zipball_url' => $zipEntry ? $zipEntry['url'] : '',
                'published_at' => $r['released_at'] ?? date('c'),
                '_source'     => $source,
            ];
        }, $data));
    }

    /**
     * @param array<string, mixed> $release
     * @throws NetworkException
     */
    public function download(array $release, string $targetDir): void
    {
        $url = (string) ($release['zipball_url'] ?? '');

        if ($url === '') {
            throw new NetworkException("GitLab release has no zip source URL — cannot download.");
        }

        $source  = $release['_source'] ?? null;
        $client  = $source instanceof Source ? $this->client($source) : $this->anonymousClient();
        $content = $client->get($url);

        Checksum::verify($content, $release);
        Archive::extractString($content, $targetDir);
    }

    /**
     * @param array<string, mixed> $release
     */
    public function verify(string $content, array $release): bool
    {
        return Checksum::verify($content, $release);
    }

    public function token(Source $source): ?string
    {
        return $this->explicitToken ?? $this->tokenStore->resolve($source);
    }

    // ── Internals ─────────────────────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
     */
    private function branchFallback(HttpClient $client, Source $source): array
    {
        $projectId = urlencode("{$source->vendor}/{$source->slug}");
        $apiBase   = $source->apiBase();
        $repoUrl   = "{$apiBase}/projects/{$projectId}";

        $result = $client->getWithStatus($repoUrl);

        if ($result['status'] !== 200) {
            throw new NetworkException(
                "Cannot reach GitLab repository [{$source}] (HTTP {$result['status']})."
            );
        }

        $repo       = json_decode($result['body'], true);
        $branch     = (string) ($repo['default_branch'] ?? 'main');
        $archiveUrl = "{$apiBase}/projects/{$projectId}/repository/archive.zip?sha={$branch}";

        return [[
            'tag_name'           => $branch,
            'name'               => "Branch: {$branch}",
            'zipball_url'        => $archiveUrl,
            'published_at'       => date('c'),
            'is_branch_fallback' => true,
            '_source'            => $source,
        ]];
    }

    private function client(Source $source): HttpClient
    {
        $client = $this->baseClient();
        $tok    = $this->token($source);

        if ($tok !== null) {
            $client = $client->withToken(parse_url($source->apiBase(), PHP_URL_HOST) ?? 'gitlab.com', $tok);
        }

        return $client;
    }

    private function anonymousClient(): HttpClient
    {
        return $this->baseClient();
    }

    private function baseClient(): HttpClient
    {
        return (new HttpClient())
            ->withHeader('Accept', 'application/json');
    }
}
