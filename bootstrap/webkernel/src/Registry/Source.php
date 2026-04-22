<?php declare(strict_types=1);

namespace Webkernel\Registry;

    /**
    * Immutable value object that fully describes where a module (or the kernel
    * itself) comes from and how to reach it.
    *
    * A Source is provider-agnostic: the same struct is passed to GitHub, GitLab,
    * Bitbucket, Numerimondes, and any custom adapter. The adapter decides how
    * to interpret the fields; this Source does not contain adapter logic.
    *
    * Canonical ID format:  registry-slug::vendor/slug
    * Examples:
    *   github-com::emyassine/webkernel-module
    *   webkernelphp-com::test/test
    *   webkernel::aptitudes/system      (first-party aptitude)
    *   git-numerimondes-com::acme/erp   (second-party, private)
    */
    final readonly class Source
    {
        /**
        * @param Providers   $provider   The registry this source belongs to.
        * @param string      $registry   Hostname string (mirrors provider->value, kept for display).
        * @param string      $vendor     Owner / organisation slug.
        * @param string      $slug       Repository / module slug.
        * @param string      $party      "first" | "second" | "third"
        * @param string|null $version    Semver constraint or tag; null means "latest".
        * @param bool        $private    Whether authentication is known to be required.
        * @param string|null $customBase Override API base URL (used when provider = Custom).
        */
        public function __construct(
            public readonly Providers   $provider,
            public readonly string      $registry,
            public readonly string      $vendor,
            public readonly string      $slug,
            public readonly string      $party     = 'third',
            public readonly ?string     $version   = null,
            public readonly bool        $private   = false,
            public readonly ?string     $customBase = null,
        ) {}

        // ── Factory helpers ───────────────────────────────────────────────────────

        /**
        * Parse a canonical ID string into a Source.
        *
        * Supported formats:
        *   "github-com::emyassine/webkernel-module"
        *   "github.com::emyassine/webkernel-module"   (dot form also accepted)
        *
        * @throws \InvalidArgumentException
        */
        public static function fromId(string $id, string $party = 'third', ?string $version = null): self
        {
            if (!preg_match('/^([^:]+)::([^\/]+)\/(.+)$/', $id, $m)) {
                throw new \InvalidArgumentException("Cannot parse Source ID: [{$id}]");
            }

            [$_full /**first element ignored */, $registryRaw, $vendor, $slug] = $m;

            // Accept both "github-com" (slug form) and "github.com" (hostname form)
            $host     = str_replace('-', '.', $registryRaw);
            $provider = Providers::fromHost($host) ?? Providers::Custom;
            $registry = $provider === Providers::Custom ? $host : $provider->value;

            return new self(
                provider:  $provider,
                registry:  $registry,
                vendor:    $vendor,
                slug:      $slug,
                party:     $party,
                version:   $version,
            );
        }

        /**
        * Build a Source explicitly — preferred when constructing programmatically.
        *
        * @throws \InvalidArgumentException
        */
        public static function from(
            Providers|string $provider,
            string           $vendor,
            string           $slug,
            string           $party      = 'third',
            ?string          $version    = null,
            bool             $private    = false,
            ?string          $customBase = null,
        ): self {
            if (is_string($provider)) {
                $resolved = Providers::fromHost($provider) ?? Providers::Custom;
            } else {
                $resolved = $provider;
            }

            return new self(
                provider:   $resolved,
                registry:   $resolved === Providers::Custom ? $provider : $resolved->value,
                vendor:     $vendor,
                slug:       $slug,
                party:      $party,
                version:    $version,
                private:    $private,
                customBase: $customBase,
            );
        }

        // ── Computed properties ───────────────────────────────────────────────────

        /**
        * The canonical module ID as stored in module manifests.
        * e.g. "github-com::emyassine/webkernel-module"
        */
        public function id(): string
        {
            $registrySlug = str_replace('.', '-', $this->registry);
            return "{$registrySlug}::{$this->vendor}/{$this->slug}";
        }

        /**
        * Effective API base URL: customBase overrides the enum lookup.
        *
        * @throws \LogicException  when no base URL can be resolved
        */
        public function apiBase(): string
        {
            if ($this->customBase !== null) {
                return rtrim($this->customBase, '/');
            }

            return $this->provider->requireApiBase();
        }

        /**
        * Whether this source is known to require a token.
        * Combines the provider-level default with the per-instance flag.
        */
        public function needsAuth(): bool
        {
            return $this->private || $this->provider->requiresAuthForFreeContent();
        }

        /**
        * Human-readable string for logging and error messages.
        */
        public function __toString(): string
        {
            $v = $this->version !== null ? "@{$this->version}" : '';
            return "{$this->id()}{$v} ({$this->party}-party)";
        }
    }
