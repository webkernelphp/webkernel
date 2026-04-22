<?php declare(strict_types=1);

namespace Webkernel\Integration\Git;

use Webkernel\Integration\Git\Contracts\GitHostAdapter;
use Webkernel\Integration\Git\Exceptions\NetworkException;
use Webkernel\Registry\Providers;
use Webkernel\Registry\Source;
use Webkernel\Registry\Token;

/**
 * GitHub adapter — public and private repos, release listing with branch
 * fallback, redirect-aware zip downloads, SHA-256 checksum verification.
 *
 * Token resolution order:
 *   1. Token injected via withToken()
 *   2. Token from Registry\Token store (encrypted, persistent)
 *   3. null (anonymous — works for public repos)
 *
 * The adapter never prompts for a token. That responsibility belongs to the
 * caller (command or Filament page). Call needsToken(Source) to check first.
 */
final class GitHubAdapter implements GitHostAdapter
{
    private const API_VERSION = '2022-11-28';

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
        return $source->provider === Providers::GitHub;
    }

    /**
     * @return array<int, array<string, mixed>>
     * @throws NetworkException
     */
    public function releases(Source $source, bool $includePreReleases = false): array
    {
        $client  = $this->client($source);
        $apiBase = $source->apiBase();
        $url     = "{$apiBase}/repos/{$source->vendor}/{$source->slug}/releases";

        $result = $client->getWithStatus($url);

        if ($result['status'] === 404) {
            return $this->branchFallback($client, $source);
        }

        if ($result['status'] !== 200) {
            throw new NetworkException(
                "GitHub releases endpoint returned HTTP {$result['status']} for [{$source}]."
            );
        }

        $data = json_decode($result['body'], true);

        if (!is_array($data) || empty($data)) {
            return $this->branchFallback($client, $source);
        }

        $filtered = array_values(array_filter($data, static function (mixed $r) use ($includePreReleases): bool {
            if (!is_array($r))                                                    { return false; }
            if (($r['draft'] ?? false) === true)                                  { return false; }
            if (!$includePreReleases && ($r['prerelease'] ?? false) === true)     { return false; }
            return true;
        }));

        return empty($filtered) ? $this->branchFallback($client, $source) : $filtered;
    }

    /**
     * @param array<string, mixed> $release
     * @throws NetworkException
     */
    public function download(array $release, string $targetDir): void
    {
        $url = (string) ($release['zipball_url'] ?? '');

        if ($url === '') {
            throw new NetworkException("Release has no zipball_url — cannot download.");
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
        $apiBase = $source->apiBase();
        $result  = $client->getWithStatus("{$apiBase}/repos/{$source->vendor}/{$source->slug}");

        if ($result['status'] !== 200) {
            throw new NetworkException(
                "Cannot reach repository [{$source}] (HTTP {$result['status']})."
            );
        }

        $repo   = json_decode($result['body'], true);
        $branch = (string) ($repo['default_branch'] ?? 'main');

        return [[
            'tag_name'           => $branch,
            'name'               => "Branch: {$branch}",
            'zipball_url'        => "{$apiBase}/repos/{$source->vendor}/{$source->slug}/zipball/{$branch}",
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
            $client = $client
                ->withToken('github.com', $tok)
                ->withToken('codeload.github.com', $tok)
                ->withToken('githubusercontent.com', $tok);
        }

        return $client;
    }

    private function baseClient(): HttpGitClient
    {
        return (new HttpGitClient())
            ->withHeader('Accept', 'application/vnd.github+json')
            ->withHeader('X-GitHub-Api-Version', self::API_VERSION);
    }
}
