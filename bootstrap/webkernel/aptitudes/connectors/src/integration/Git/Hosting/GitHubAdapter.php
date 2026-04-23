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
 * GitHub adapter — public and private repos, release listing with branch
 * fallback, redirect-aware zip downloads, SHA-256 checksum verification.
 *
 * Token resolution order:
 *   1. Token injected via withToken()
 *   2. Token from Registry\Token store (encrypted, persistent)
 *   3. null (anonymous — works for public repos)
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
            if (!is_array($r))                                                { return false; }
            if (($r['draft'] ?? false) === true)                              { return false; }
            if (!$includePreReleases && ($r['prerelease'] ?? false) === true) { return false; }
            return true;
        }));

        return empty($filtered) ? $this->branchFallback($client, $source) : $filtered;
    }

    /**
     * Fetch all git tags for a repository — paginated, all pages.
     * Returns full GitHub tag payloads including commit SHA and node_id.
     *
     * @return array<int, array<string, mixed>>
     * @throws NetworkException
     */
    public function tags(Source $source): array
    {
        $client  = $this->client($source);
        $apiBase = $source->apiBase();
        $all     = [];
        $page    = 1;

        do {
            $url    = "{$apiBase}/repos/{$source->vendor}/{$source->slug}/tags?per_page=100&page={$page}";
            $result = $client->getWithStatus($url);

            if ($result['status'] !== 200) {
                throw new NetworkException(
                    "GitHub tags endpoint returned HTTP {$result['status']} for [{$source}]."
                );
            }

            $data = json_decode($result['body'], true);

            if (!is_array($data) || empty($data)) {
                break;
            }

            $all  = array_merge($all, $data);
            $page++;
        } while (count($data) === 100); // GitHub returns exactly 100 per full page

        return $all;
    }

    /**
     * Fetch the annotated tag object (with message) for a given tag name.
     * Returns the tag object including the annotation message, or null if not found.
     *
     * @return array<string, mixed>|null
     * @throws NetworkException
     */
    public function annotatedTag(Source $source, string $tagName): ?array
    {
        $client  = $this->client($source);
        $apiBase = $source->apiBase();

        $url = "{$apiBase}/repos/{$source->vendor}/{$source->slug}/git/refs/tags/{$tagName}";
        $result = $client->getWithStatus($url);

        if ($result['status'] === 404) {
            return null;
        }

        if ($result['status'] !== 200) {
            throw new NetworkException(
                "GitHub git/refs/tags endpoint returned HTTP {$result['status']} for [{$source}] tag={$tagName}."
            );
        }

        $ref = json_decode($result['body'], true);
        if (!is_array($ref) || ($ref['object']['type'] ?? '') !== 'tag') {
            return null;
        }

        $tagSha = $ref['object']['sha'] ?? null;
        if (!$tagSha) {
            return null;
        }

        $tagUrl = "{$apiBase}/repos/{$source->vendor}/{$source->slug}/git/tags/{$tagSha}";
        $tagResult = $client->getWithStatus($tagUrl);

        if ($tagResult['status'] !== 200) {
            return null;
        }

        $data = json_decode($tagResult['body'], true);

        return is_array($data) ? $data : null;
    }

    /**
     * Query the GitHub rate-limit API (free — does not consume quota).
     * Returns remaining request count and the reset timestamp.
     *
     * @return array{remaining: int, reset_at: \DateTimeImmutable}
     * @throws NetworkException
     */
    public function rateLimit(Source $source): array
    {
        $client = $this->client($source);
        $result = $client->getWithHeaders("{$source->apiBase()}/rate_limit");

        if ($result['status'] !== 200) {
            throw new NetworkException("GitHub rate_limit endpoint returned HTTP {$result['status']}.");
        }

        $data = json_decode($result['body'], true);
        $core = $data['resources']['core'] ?? $data['rate'] ?? [];

        $remaining = (int) ($core['remaining'] ?? ($result['headers']['x-ratelimit-remaining'] ?? 60));
        $reset     = (int) ($core['reset']     ?? ($result['headers']['x-ratelimit-reset']     ?? time() + 3600));

        return [
            'remaining' => $remaining,
            'reset_at'  => (new \DateTimeImmutable())->setTimestamp($reset),
        ];
    }

    /**
     * Fetch only the latest release — single API call, lighter than releases().
     *
     * @return array<string, mixed>
     * @throws NetworkException
     */
    public function latestRelease(Source $source): array
    {
        $client = $this->client($source);
        $url    = "{$source->apiBase()}/repos/{$source->vendor}/{$source->slug}/releases/latest";
        $result = $client->getWithStatus($url);

        if ($result['status'] === 404) {
            $all = $this->releases($source);
            return $all[0] ?? throw new NetworkException("No releases found for [{$source}].");
        }

        if ($result['status'] !== 200) {
            throw new NetworkException(
                "GitHub latest-release endpoint returned HTTP {$result['status']} for [{$source}]."
            );
        }

        $data = json_decode($result['body'], true);

        if (!is_array($data) || empty($data)) {
            throw new NetworkException("Empty response from GitHub latest-release endpoint for [{$source}].");
        }

        return $data;
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
