<?php declare(strict_types=1);

namespace Webkernel\Http\Git;

use Webkernel\Http\Git\Exceptions\NetworkException;

/**
 * Redirect-aware cURL HTTP client.
 *
 * Shared by all git-hosting adapters. Does not know about Laravel or Symfony —
 * it is safe to call from CLI tools, queue workers, and Octane workers alike.
 *
 * Features:
 *   - Manual redirect following (avoids CURLOPT_FOLLOWLOCATION losing auth headers)
 *   - Configurable auth header injection per-domain
 *   - Progress callback forwarded from CURLOPT_PROGRESSFUNCTION
 *   - Configurable SSL verification (insecure mode for dev environments)
 */
final class HttpGitClient
{
    private const MAX_REDIRECTS   = 10;
    private const CONNECT_TIMEOUT = 30;
    private const DOWNLOAD_TIMEOUT = 600;

    private bool $insecure = false;

    /** @var array<string, string>  host => bearer token */
    private array $tokens = [];

    /** @var array<string, string>  header name => value (applied to every request) */
    private array $defaultHeaders = [];

    // ── Configuration ─────────────────────────────────────────────────────────

    public function withInsecure(bool $insecure = true): self
    {
        $clone           = clone $this;
        $clone->insecure = $insecure;
        return $clone;
    }

    /**
     * Register a bearer token that will be injected when the request host
     * (or any redirect host) matches $host.
     */
    public function withToken(string $host, string $token): self
    {
        $clone                  = clone $this;
        $clone->tokens[$host]   = $token;
        return $clone;
    }

    /**
     * Add a header sent on every request regardless of host.
     */
    public function withHeader(string $name, string $value): self
    {
        $clone                         = clone $this;
        $clone->defaultHeaders[$name]  = $value;
        return $clone;
    }

    // ── Public interface ──────────────────────────────────────────────────────

    /**
     * GET request, returns the response body as a string.
     *
     * @param callable|null $progress  function(int $downloadTotal, int $downloaded): void
     * @throws NetworkException
     */
    public function get(string $url, ?callable $progress = null): string
    {
        return $this->request('GET', $url, null, $progress);
    }

    /**
     * GET request that returns both status code and body.
     *
     * @return array{status: int, body: string}
     * @throws NetworkException
     */
    public function getWithStatus(string $url): array
    {
        return $this->requestWithStatus('GET', $url);
    }

    // ── Core ──────────────────────────────────────────────────────────────────

    /**
     * @throws NetworkException
     */
    private function request(
        string   $method,
        string   $url,
        ?string  $body     = null,
        ?callable $progress = null,
    ): string {
        $result = $this->requestWithStatus($method, $url, $body, $progress);

        if ($result['status'] < 200 || $result['status'] >= 300) {
            throw new NetworkException(
                "HTTP {$result['status']} from [{$url}]"
            );
        }

        return $result['body'];
    }

    /**
     * @throws NetworkException
     */
    private function requestWithStatus(
        string    $method,
        string    $url,
        ?string   $body     = null,
        ?callable $progress = null,
    ): array {
        $currentUrl = $url;
        $hops       = 0;

        while ($hops++ < self::MAX_REDIRECTS) {
            $ch = curl_init($currentUrl);

            if ($ch === false) {
                throw new NetworkException('cURL init failed.');
            }

            $headers = $this->buildHeaders($currentUrl);

            $options = [
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_HEADER          => true,
                CURLOPT_FOLLOWLOCATION  => false,
                CURLOPT_HTTPHEADER      => $headers,
                CURLOPT_TIMEOUT         => $body !== null ? self::DOWNLOAD_TIMEOUT : self::CONNECT_TIMEOUT,
                CURLOPT_CONNECTTIMEOUT  => self::CONNECT_TIMEOUT,
                CURLOPT_CUSTOMREQUEST   => $method,
            ];

            if ($progress !== null) {
                $options[CURLOPT_NOPROGRESS]        = false;
                $options[CURLOPT_PROGRESSFUNCTION]  = static function (
                    mixed $ch,
                    float|int $dlTotal,
                    float|int $dlNow,
                    float|int $ulTotal,
                    float|int $ulNow,
                ) use ($progress): int {
                    $progress((int) $dlTotal, (int) $dlNow);
                    return 0;
                };
            }

            if ($body !== null) {
                $options[CURLOPT_POSTFIELDS] = $body;
            }

            if ($this->insecure) {
                $options[CURLOPT_SSL_VERIFYPEER] = false;
                $options[CURLOPT_SSL_VERIFYHOST] = false;
            }

            curl_setopt_array($ch, $options);

            $raw   = curl_exec($ch);
            $error = curl_error($ch);
            $code  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $hSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);

            curl_close($ch);

            if ($raw === false) {
                throw new NetworkException("cURL error: {$error}");
            }

            $rawStr     = (string) $raw;
            $headerText = substr($rawStr, 0, $hSize);
            $responseBody = substr($rawStr, $hSize);

            if ($code >= 300 && $code < 400) {
                $location = $this->extractHeader($headerText, 'Location');

                if ($location === null) {
                    throw new NetworkException("Redirect ({$code}) without Location header from [{$currentUrl}].");
                }

                $currentUrl = $this->resolveUrl($currentUrl, $location);
                continue;
            }

            return ['status' => $code, 'body' => $responseBody];
        }

        throw new NetworkException("Too many redirects for [{$url}].");
    }

    // ── Header helpers ────────────────────────────────────────────────────────

    /**
     * @return list<string>
     */
    private function buildHeaders(string $url): array
    {
        $host    = (string) (parse_url($url, PHP_URL_HOST) ?: '');
        $headers = [];

        foreach ($this->defaultHeaders as $name => $value) {
            $headers[] = "{$name}: {$value}";
        }

        foreach ($this->tokens as $tokenHost => $token) {
            if (str_contains($host, $tokenHost)) {
                $headers[] = "Authorization: Bearer {$token}";
                break;
            }
        }

        if (!isset($this->defaultHeaders['User-Agent'])) {
            $headers[] = 'User-Agent: Webkernel/' . (defined('WEBKERNEL_VERSION') ? WEBKERNEL_VERSION : 'dev');
        }

        return $headers;
    }

    private function extractHeader(string $headers, string $name): ?string
    {
        if (preg_match('/^' . preg_quote($name, '/') . ':\s*(.+)$/im', $headers, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    private function resolveUrl(string $base, string $relative): string
    {
        if (parse_url($relative, PHP_URL_SCHEME) !== null) {
            return $relative;
        }

        $parts  = parse_url($base);
        $scheme = (string) ($parts['scheme'] ?? 'https');
        $host   = (string) ($parts['host'] ?? '');
        $port   = isset($parts['port']) ? ':' . $parts['port'] : '';

        if (str_starts_with($relative, '/')) {
            return "{$scheme}://{$host}{$port}{$relative}";
        }

        $dir  = (string) preg_replace('#/[^/]*$#', '/', (string) ($parts['path'] ?? '/'));
        $full = "{$scheme}://{$host}{$port}{$dir}{$relative}";

        // Resolve ".." segments
        while (str_contains($full, '/../')) {
            $full = (string) preg_replace('#/[^/]+/\.\./#', '/', $full, 1);
        }

        return $full;
    }
}
