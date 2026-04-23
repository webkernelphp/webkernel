<?php declare(strict_types=1);

namespace Webkernel\System\Ops\Providers;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Webkernel\System\Ops\Contracts\Provider;

/**
 * API provider for REST/HTTP sources.
 *
 * Usage:
 *   webkernel()->do()
 *       ->from(ApiProvider::get('https://api.example.com/users'))
 *       ->filter(fn($u) => $u['active'])
 *       ->run();
 */
final class ApiProvider implements Provider
{
    private ?Closure $transform = null;
    private array $responseHeaders = [];
    private ?string $rawContent = null;
    private bool $isBinary = false;

    public function __construct(
        private readonly string $url,
        private readonly string $method = 'GET',
        private readonly ?array $payload = null,
    ) {}

    public static function get(string $url): self
    {
        return new self($url, 'GET');
    }

    public static function post(string $url, array $payload): self
    {
        return new self($url, 'POST', $payload);
    }

    /**
     * Transform API response with closure.
     */
    public function transform(Closure $fn): self
    {
        $clone = clone $this;
        $clone->transform = $fn;
        return $clone;
    }

    public function fetch(): Collection|string
    {
        try {
            $response = match ($this->method) {
                'POST' => Http::post($this->url, $this->payload ?? []),
                'GET' => Http::get($this->url),
                default => Http::get($this->url),
            };

            // Capture response headers
            $this->responseHeaders = $response->headers();

            // Detect if this is binary content (e.g., ZIP archive)
            $contentType = $this->header('Content-Type') ?? '';
            if (str_contains($contentType, 'application/zip') ||
                str_contains($contentType, 'application/x-zip') ||
                str_ends_with($this->url, '.zip') ||
                str_ends_with($this->url, '.tar.gz') ||
                str_ends_with($this->url, '.tgz')) {

                $this->isBinary = true;
                $this->rawContent = $response->body();
                return collect([]); // Return empty collection, content accessible via getRawContent()
            }

            // JSON response
            $data = $response->json() ?? [];
            $data = is_array($data) ? $data : [$data];

            if ($this->transform) {
                return collect($data)->map($this->transform);
            }

            return collect($data);
        } catch (\Throwable $e) {
            throw new \RuntimeException("API fetch failed: " . $e->getMessage());
        }
    }

    /**
     * Get raw response content (for binary downloads).
     */
    public function getRawContent(): ?string
    {
        return $this->rawContent;
    }

    /**
     * Check if response is binary content.
     */
    public function isBinary(): bool
    {
        return $this->isBinary;
    }

    public function headers(): array
    {
        return $this->responseHeaders;
    }

    public function name(): string
    {
        return "API::" . parse_url($this->url, PHP_URL_HOST);
    }

    /**
     * Get specific header value.
     */
    public function header(string $name, mixed $default = null): mixed
    {
        return $this->responseHeaders[$name] ?? $default;
    }

    /**
     * Get rate limit info from common header names.
     */
    public function rateLimit(): array
    {
        return [
            'limit' => $this->header('X-RateLimit-Limit') ?? $this->header('RateLimit-Limit'),
            'remaining' => $this->header('X-RateLimit-Remaining') ?? $this->header('RateLimit-Remaining'),
            'reset' => $this->header('X-RateLimit-Reset') ?? $this->header('RateLimit-Reset'),
        ];
    }

    /**
     * Check CORS and embedding permissions.
     */
    public function allowedFraming(): bool
    {
        $frameOption = $this->header('X-Frame-Options');
        if (!$frameOption) {
            return true; // Not restricted
        }
        return !in_array(strtoupper($frameOption), ['DENY', 'SAMEORIGIN']);
    }

    /**
     * Get Content Security Policy.
     */
    public function csp(): ?string
    {
        return $this->header('Content-Security-Policy');
    }
}
