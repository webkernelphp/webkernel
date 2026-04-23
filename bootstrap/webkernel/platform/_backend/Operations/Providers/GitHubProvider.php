<?php declare(strict_types=1);

namespace Webkernel\System\Operations\Providers;

use Webkernel\Integration\Git\Hosting\GitHubAdapter;
use Webkernel\Registry\Providers;
use Webkernel\Registry\Source;
use Webkernel\Registry\Token;
use Webkernel\System\Operations\SourceProviderWithMetadata;

final class GitHubProvider implements SourceProviderWithMetadata
{
    private GitHubAdapter $adapter;
    private Source $source;
    private ?string $explicitToken = null;

    public function __construct(
        private readonly string $owner,
        private readonly string $slug,
        ?string $token = null,
    ) {
        $this->explicitToken = $token;
        $this->adapter = new GitHubAdapter(new Token());
        $this->source = Source::from(
            provider: Providers::GitHub,
            vendor: $owner,
            slug: $slug,
            party: 'first',
            version: null,
        );
    }

    public static function forWebkernel(?string $token = null): self
    {
        return new self('webkernelphp', 'foundation', $token);
    }

    public function releases(): array
    {
        $adapter = $this->adapter;
        if ($this->explicitToken !== null) {
            $adapter = $adapter->withToken($this->explicitToken);
        }

        try {
            return $adapter->releases($this->source, includePreReleases: false);
        } catch (\Throwable $e) {
            throw new \RuntimeException("Failed to fetch releases from GitHub: " . $e->getMessage());
        }
    }

    public function metadata(array $release): ?array
    {
        $tagName = $release['tag_name'] ?? null;
        if ($tagName === null) {
            return null;
        }

        $adapter = $this->adapter;
        if ($this->explicitToken !== null) {
            $adapter = $adapter->withToken($this->explicitToken);
        }

        try {
            $tagObj = $adapter->annotatedTag($this->source, $tagName);

            if ($tagObj === null || ($tagObj['message'] ?? '') === '') {
                return null;
            }

            return $this->parseAnnotationMetadata($tagObj['message']);
        } catch (\Throwable) {
            return null;
        }
    }

    public function download(string $artifactUrl, string $targetDir): void
    {
        $adapter = $this->adapter;
        if ($this->explicitToken !== null) {
            $adapter = $adapter->withToken($this->explicitToken);
        }

        try {
            $content = (new \Webkernel\Integration\Git\HttpGitClient())->get($artifactUrl);

            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            \Webkernel\Integration\Git\Archive::extractString($content, $targetDir);
        } catch (\Throwable $e) {
            throw new \RuntimeException("Failed to download artifact: " . $e->getMessage());
        }
    }

    public function name(): string
    {
        return "GitHub ({$this->owner}/{$this->slug})";
    }

    private function parseAnnotationMetadata(string $message): ?array
    {
        $lines = explode("\n", $message);

        if (count($lines) < 3) {
            return null;
        }

        $metaJson = trim($lines[2]);
        if ($metaJson === '' || !str_starts_with($metaJson, '{')) {
            return null;
        }

        $metaEnd = strpos($metaJson, '}');
        if ($metaEnd === false) {
            return null;
        }

        $metaJson = substr($metaJson, 0, $metaEnd + 1);
        $meta = json_decode($metaJson, true);

        if (!is_array($meta)) {
            return null;
        }

        return array_filter([
            'codename' => $meta['codename'] ?? null,
            'notes' => $meta['notes'] ?? null,
            'video' => $meta['video'] ?? null,
            'features' => $meta['features'] ?? null,
            'doc_links' => $meta['doc_links'] ?? null,
        ], static fn ($v) => $v !== null);
    }
}
