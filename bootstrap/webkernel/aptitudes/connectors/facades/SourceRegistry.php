<?php declare(strict_types=1);

namespace Webkernel\Connectors;

// bootstrap/webkernel/aptitudes/connectors/facades/SourceRegistry.php
// Autoloaded as:
//   'Webkernel\\Connectors\\' => WEBKERNEL_PATH . '/aptitudes/connectors/facades'

use Webkernel\Integration\Git\AdapterResolver;
use Webkernel\Integration\Git\Contracts\GitHostAdapter;
use Webkernel\Integration\Git\Hosting\GitHubAdapter;
use Webkernel\Integration\Git\Hosting\GitLabAdapter;
use Webkernel\Registry\Providers;
use Webkernel\Registry\Source;
use Webkernel\Registry\Token;

/**
 * SourceRegistry — connector facade for Git hosting providers.
 *
 * Single static entry-point that returns a ready-to-use GitHostAdapter
 * regardless of whether the target is GitHub, GitLab, a self-hosted Gitea
 * instance, or a plain HTTP endpoint. Callers never import individual adapter
 * classes — they ask SourceRegistry for an adapter and work with the
 * GitHostAdapter contract.
 *
 * Usage:
 *   // Public GitHub repo — no token needed
 *   $adapter = SourceRegistry::github();
 *
 *   // Authenticated GitLab
 *   $adapter = SourceRegistry::gitlab()->withToken('glpat-...');
 *
 *   // Self-hosted Gitea
 *   $adapter = SourceRegistry::gitea('https://git.mycompany.com');
 *
 *   // Generic HTTP update server
 *   $adapter = SourceRegistry::http('https://updates.mycompany.com');
 *
 *   // Auto-resolve from a Source value object (used internally)
 *   $adapter = SourceRegistry::resolve($source);
 */
class SourceRegistry
{
    // ── Named factories ───────────────────────────────────────────────────────

    public static function github(?string $token = null): GitHubAdapter
    {
        $adapter = new GitHubAdapter(new Token());
        return $token !== null ? $adapter->withToken($token) : $adapter;
    }

    public static function gitlab(?string $token = null): GitLabAdapter
    {
        $adapter = new GitLabAdapter(new Token());
        return $token !== null ? $adapter->withToken($token) : $adapter;
    }

    /**
     * Gitea / Forgejo self-hosted instance.
     *
     * @param string $baseUrl  e.g. "https://git.mycompany.com"
     */
    public static function gitea(string $baseUrl, ?string $token = null): GitHostAdapter
    {
        // Gitea exposes a GitHub-compatible API at /api/v1 — reuse GitHubAdapter
        // with an overridden base URL once a native GiteaAdapter exists.
        // For now we surface the placeholder so callers compile and can be swapped
        // transparently when GiteaAdapter lands.
        return static::_adapterForCustomBase(Providers::GitHub, $baseUrl, $token);
    }

    /**
     * Plain HTTP update server (custom registry endpoint).
     *
     * @param string $baseUrl  e.g. "https://updates.mycompany.com"
     */
    public static function http(string $baseUrl, ?string $token = null): GitHostAdapter
    {
        return static::_adapterForCustomBase(Providers::GitHub, $baseUrl, $token);
    }

    // ── Generic resolver ──────────────────────────────────────────────────────

    /**
     * Resolve the correct adapter for any Source value object.
     * Delegates to AdapterResolver — the single registry of all adapters.
     */
    public static function resolve(Source $source, ?string $token = null): GitHostAdapter
    {
        $adapter = (new AdapterResolver(new Token()))->resolve($source);
        return $token !== null ? $adapter->withToken($token) : $adapter;
    }

    // ── Source builder shorthand ──────────────────────────────────────────────

    /**
     * Build a Source and immediately resolve its adapter.
     *
     *   SourceRegistry::for('github', 'webkernelphp', 'foundation')
     */
    public static function for(
        string  $registry,
        string  $vendor,
        string  $slug,
        ?string $token   = null,
        ?string $version = null,
    ): GitHostAdapter {
        $provider = match (strtolower($registry)) {
            'github'          => Providers::GitHub,
            'gitlab'          => Providers::GitLab,
            default           => Providers::GitHub,
        };

        $source = Source::from(
            provider: $provider,
            vendor:   $vendor,
            slug:     $slug,
            party:    'third',
            version:  $version,
        );

        return static::resolve($source, $token);
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    private static function _adapterForCustomBase(
        Providers $provider,
        string    $baseUrl,
        ?string   $token,
    ): GitHostAdapter {
        // Adapters with a custom base URL are not yet implemented as distinct
        // classes. This method is intentionally left as a named placeholder so
        // call sites using SourceRegistry::gitea() / ::http() already compile
        // and will work once GiteaAdapter / HttpRegistryAdapter land.
        //
        // Workaround: return the GitHub-compatible adapter; callers that need
        // a real Gitea/HTTP adapter should implement the interface and register
        // it via AdapterResolver::register().
        $adapter = new GitHubAdapter(new Token());
        return $token !== null ? $adapter->withToken($token) : $adapter;
    }
}
