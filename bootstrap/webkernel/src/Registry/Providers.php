<?php declare(strict_types=1);

namespace Webkernel\Registry;

/**
 * Canonical registry identifiers.
 *
 * Each case maps 1-to-1 with a key in WEBKERNEL_MODULE_REGISTRIES.
 * The API base URL is resolved at runtime from that constant so that
 * the values can be overridden by environment configuration without
 * touching this file.
 *
 * Visibility:
 *   public  — authentication optional (free modules)
 *   private — authentication required
 *   unknown — the adapter must probe at runtime
 */
enum Providers: string
{
    case Webkernel    = 'webkernelphp.com';
    case GitHub       = 'github.com';
    case GitLab       = 'gitlab.com';
    case Bitbucket    = 'bitbucket.org';
    case Numerimondes = 'git.numerimondes.com';
    case Assembla     = 'assembla.com';
    case AWS          = 'aws.amazon.com';
    case Azure        = 'azure.com';
    case Custom       = 'custom';

    // ── API base URLs ─────────────────────────────────────────────────────────

    /**
     * Return the API base URL as declared in WEBKERNEL_MODULE_REGISTRIES,
     * or null when the registry does not have a known public REST endpoint.
     */
    public function apiBase(): ?string
    {
        $map = defined('WEBKERNEL_MODULE_REGISTRIES')
            ? (array) WEBKERNEL_MODULE_REGISTRIES
            : [];

        return $map[$this->value] ?? null;
    }

    /**
     * Convenience shorthand used by adapters to build endpoint URLs.
     * Throws when the registry has no configured API base
     * (e.g. Assembla, AWS, Azure in default config).
     *
     * @throws \LogicException
     */
    public function requireApiBase(): string
    {
        $base = $this->apiBase();

        if ($base === null) {
            throw new \LogicException(
                "Registry [{$this->value}] has no API base URL configured in WEBKERNEL_MODULE_REGISTRIES."
            );
        }

        return rtrim($base, '/');
    }

    // ── Naming helpers ────────────────────────────────────────────────────────

    /**
     * Normalise a registry hostname string into a Providers case.
     * Returns null for unrecognised hostnames (caller treats them as Custom).
     */
    public static function fromHost(string $host): ?self
    {
        $host = strtolower(trim($host));

        foreach (self::cases() as $case) {
            if ($case->value === $host) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Convert the registry hostname into a filesystem-safe slug.
     * e.g. "github.com" -> "github-com"
     */
    public function slug(): string
    {
        return str_replace('.', '-', $this->value);
    }

    // ── Auth hints ────────────────────────────────────────────────────────────

    /**
     * Whether this registry requires a token for public (free) module downloads.
     * The official Webkernel registry does not require auth for free modules.
     */
    public function requiresAuthForFreeContent(): bool
    {
        return match ($this) {
            self::Webkernel    => false,
            self::GitHub       => false,
            self::GitLab       => false,
            self::Bitbucket    => false,
            self::Numerimondes => true,
            self::Assembla     => true,
            self::AWS          => true,
            self::Azure        => true,
            self::Custom       => false,
        };
    }

    /**
     * Whether this registry requires a token for paid / private content.
     * All registries require auth for private repositories.
     */
    public function supportsTokenAuth(): bool
    {
        return $this !== self::Custom;
    }
}
