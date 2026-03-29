<?php declare(strict_types=1);

namespace Webkernel\Http\Git;

use Webkernel\Http\Git\Contracts\GitHostAdapter;
use Webkernel\Http\Git\Exceptions\NetworkException;
use Webkernel\Registry\Source;
use Webkernel\Registry\Token;

/**
 * GitHub adapter.
 *
 * Handles public and private repositories, release listing with branch fallback,
 * redirect-aware zip downloads, and checksum verification.
 *
 * Token resolution order:
 *   1. Token explicitly injected via withToken()
 *   2. Token resolved from the Registry\Token store (encrypted, persistent)
 *   3. null (anonymous, works for public repos)
 *
 * The adapter does NOT prompt the user for a token. That responsibility belongs
 * to the caller (Installer, Updater, or a console command). The adapter exposes
 * needsToken(Source) to let the caller decide when to ask.
 */
final class GitHubAdapter implements GitHostAdapter
{
    private const API_VERSION = '2022-11-28';

    private ?string $explicitToken = null;

    public function __construct(private readonly Token $tokenStore) {}

    // ── Fluent builder ────────────────────────────────────────────────────────

    /**
     * Inject a token directly, bypassing the token store.
     * Used by Installer / Updater when the user provided a token at call time.
     */
    public function withToken(string $token): self
    {
        $clone                = clone $this;
        $clone->explicitToken = $token;
        return $clone;
    }

    // ── GitHostAdapter ────────────────────────────────────────────────────────

    public function supports(Source $source): bool
    {
        return $source->provider === \Webkernel\Registry\Providers::GitHub;
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
            if (!is_array($r))                             { return false; }
            if (($r['draft'] ?? false) === true)           { return false; }
            if (!$includePreReleases && ($r['prerelease'] ?? false) === true) { return false; }
            return true;
        }));

        if (empty($filtered)) {
            return $this->branchFallback($client, $source);
        }

        return $filtered;
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
        $client  = $source instanceof Source ? $this->client($source) : $this->anonymousClient();

        $content = $client->get($url, static function (int $total, int $now): void {
            // Progress hook — callers may wrap this adapter to intercept it
        });

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
    private function branchFallback(HttpGitClient $client, Source $source): array
    {
        $apiBase = $source->apiBase();
        $repoUrl = "{$apiBase}/repos/{$source->vendor}/{$source->slug}";

        $result = $client->getWithStatus($repoUrl);

        if ($result['status'] !== 200) {
            throw new NetworkException(
                "Cannot reach repository [{$source}] (HTTP {$result['status']})."
            );
        }

        $repo   = json_decode($result['body'], true);
        $branch = (string) ($repo['default_branch'] ?? 'main');

        return [[
            'tag_name'          => $branch,
            'name'              => "Branch: {$branch}",
            'zipball_url'       => "{$apiBase}/repos/{$source->vendor}/{$source->slug}/zipball/{$branch}",
            'published_at'      => date('c'),
            'is_branch_fallback' => true,
            '_source'           => $source,
        ]];
    }

    private function client(Source $source): HttpGitClient
    {
        $client = $this->baseClient();
        $tok    = $this->token($source);

        if ($tok !== null) {
            $client = $client->withToken('github.com', $tok)
                             ->withToken('codeload.github.com', $tok)
                             ->withToken('githubusercontent.com', $tok);
        }

        return $client;
    }

    private function anonymousClient(): HttpGitClient
    {
        return $this->baseClient();
    }

    private function baseClient(): HttpGitClient
    {
        return (new HttpGitClient())
            ->withHeader('Accept', 'application/vnd.github+json')
            ->withHeader('X-GitHub-Api-Version', self::API_VERSION);
    }
}
