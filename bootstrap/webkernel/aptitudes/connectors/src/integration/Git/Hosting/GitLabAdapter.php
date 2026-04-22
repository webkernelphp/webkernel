<?php declare(strict_types=1);

namespace Webkernel\Integration\Git\Hosting;

use Webkernel\Integration\Git\Archive;
use Webkernel\Integration\Git\Checksum;
use Webkernel\Integration\Git\Contracts\GitHostAdapter;
use Webkernel\Integration\Git\Exceptions\NetworkException;
use Webkernel\Integration\Git\HttpGitClient;
use Webkernel\Registry\Providers;
use Webkernel\Registry\Source;
use Webkernel\Registry\Token;

/**
 * GitLab adapter — covers gitlab.com, self-hosted Gitea/Forgejo, and
 * git.numerimondes.com (all speak the GitLab API v4 dialect).
 *
 * Release assets are normalised to the same shape as GitHubAdapter so
 * callers are provider-agnostic.
 */
final class GitLabAdapter implements GitHostAdapter
{
    private ?string $explicitToken = null;

    public function __construct(private readonly Token $tokenStore) {}

    public function withToken(string $token): static
    {
        $clone                = clone $this;
        $clone->explicitToken = $token;
        return $clone;
    }

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

        return array_values(array_map(static function (array $r) use ($source): array {
            $sources  = $r['assets']['sources'] ?? [];
            $zipEntry = current(array_filter($sources, static fn ($s) => ($s['format'] ?? '') === 'zip'));
            return [
                'tag_name'     => $r['tag_name']   ?? $r['name'] ?? '',
                'name'         => $r['name']        ?? '',
                'zipball_url'  => $zipEntry ? $zipEntry['url'] : '',
                'published_at' => $r['released_at'] ?? date('c'),
                '_source'      => $source,
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
        $client  = $source instanceof Source ? $this->client($source) : $this->baseClient();
        $content = $client->get($url);

        Checksum::verify($content, $release);
        Archive::extractString($content, $targetDir);
    }

    public function verify(string $content, array $release): bool
    {
        return Checksum::verify($content, $release);
    }

    public function token(Source $source): ?string
    {
        return $this->explicitToken ?? $this->tokenStore->resolve($source);
    }

    /** @return array<int, array<string, mixed>> */
    private function branchFallback(HttpGitClient $client, Source $source): array
    {
        $projectId  = urlencode("{$source->vendor}/{$source->slug}");
        $apiBase    = $source->apiBase();
        $result     = $client->getWithStatus("{$apiBase}/projects/{$projectId}");

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

    private function client(Source $source): HttpGitClient
    {
        $client = $this->baseClient();
        $tok    = $this->token($source);

        if ($tok !== null) {
            $host   = (string) (parse_url($source->apiBase(), PHP_URL_HOST) ?? 'gitlab.com');
            $client = $client->withToken($host, $tok);
        }

        return $client;
    }

    private function baseClient(): HttpGitClient
    {
        return (new HttpGitClient())->withHeader('Accept', 'application/json');
    }
}
