<?php declare(strict_types=1);

namespace Webkernel\System\Http;

use Illuminate\Support\Facades\Http;
use Filament\Notifications\Notification;

final class GithubClient
{
    private ?string $token = null;
    private ?string $lastError = null;
    private bool $rateLimited = false;

    public function __construct()
    {
        $this->token = env('GITHUB_TOKEN');
    }

    public function tags(string $owner, string $repo): ?array
    {
        return $this->fetch("/repos/{$owner}/{$repo}/tags");
    }

    public function annotatedTag(string $owner, string $repo, string $tagName): ?array
    {
        $ref = $this->fetch("/repos/{$owner}/{$repo}/git/refs/tags/{$tagName}");
        if (!$ref || ($ref['object']['type'] ?? '') !== 'tag') {
            return null;
        }

        $tagSha = $ref['object']['sha'] ?? null;
        return $tagSha ? $this->fetch("/repos/{$owner}/{$repo}/git/tags/{$tagSha}") : null;
    }

    public function isRateLimited(): bool
    {
        return $this->rateLimited;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function notifyIfRateLimited(): self
    {
        if ($this->rateLimited) {
            Notification::make()
                ->title('GitHub API Rate Limited')
                ->body('Check again in a few minutes.')
                ->warning()
                ->send();
        }
        return $this;
    }

    public function notifyIfError(): self
    {
        if ($this->lastError && !$this->rateLimited) {
            Notification::make()
                ->title('Failed to fetch release metadata')
                ->body($this->lastError)
                ->danger()
                ->send();
        }
        return $this;
    }

    private function fetch(string $endpoint): ?array
    {
        $this->lastError = null;
        $this->rateLimited = false;

        try {
            $url = "https://api.github.com{$endpoint}";
            $response = Http::acceptJson()
                ->when($this->token, fn ($r) => $r->withToken($this->token, 'token'))
                ->timeout(10)
                ->get($url);

            if ($response->status() === 403) {
                $remaining = (int) ($response->header('x-ratelimit-remaining') ?? 0);
                if ($remaining === 0) {
                    $this->rateLimited = true;
                    return null;
                }
                $this->lastError = 'Access denied to GitHub API';
                return null;
            }

            if ($response->status() === 404) {
                return null;
            }

            if (!$response->successful()) {
                $this->lastError = "GitHub API error: HTTP {$response->status()}";
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            $this->lastError = "Network error: {$e->getMessage()}";
            return null;
        }
    }
}
