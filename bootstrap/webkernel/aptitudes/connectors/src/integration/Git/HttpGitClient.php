<?php declare(strict_types=1);

namespace Webkernel\Integration\Git;

use Webkernel\Integration\Git\Exceptions\NetworkException;

/**
 * Redirect-aware cURL HTTP client shared by all git-hosting adapters.
 *
 * Safe from CLI, HTTP (Filament panel), queue workers, and Octane alike.
 * - Manual redirect following preserves auth headers across hops.
 * - Per-domain bearer token injection.
 * - SSL bypass for dev environments.
 */
final class HttpGitClient
{
    private const MAX_REDIRECTS    = 10;
    private const CONNECT_TIMEOUT  = 30;
    private const DOWNLOAD_TIMEOUT = 600;

    private bool $insecure = false;

    /** @var array<string, string>  host → bearer token */
    private array $tokens = [];

    /** @var array<string, string>  header name → value */
    private array $defaultHeaders = [];

    public function withInsecure(bool $insecure = true): self
    {
        $clone           = clone $this;
        $clone->insecure = $insecure;
        return $clone;
    }

    public function withToken(string $host, string $token): self
    {
        $clone                 = clone $this;
        $clone->tokens[$host]  = $token;
        return $clone;
    }

    public function withHeader(string $name, string $value): self
    {
        $clone                        = clone $this;
        $clone->defaultHeaders[$name] = $value;
        return $clone;
    }

    /**
     * @param callable|null $progress  fn(int $total, int $downloaded): void
     * @throws NetworkException
     */
    public function get(string $url, ?callable $progress = null): string
    {
        return $this->request('GET', $url, null, $progress);
    }

    /**
     * @return array{status: int, body: string}
     * @throws NetworkException
     */
    public function getWithStatus(string $url): array
    {
        return $this->requestWithStatus('GET', $url);
    }

    /**
     * Like getWithStatus() but also returns parsed response headers.
     *
     * @return array{status: int, body: string, headers: array<string, string>}
     * @throws NetworkException
     */
    public function getWithHeaders(string $url): array
    {
        return $this->requestWithStatus('GET', $url, null, null, true);
    }

    private function request(string $method, string $url, ?string $body = null, ?callable $progress = null): string
    {
        $result = $this->requestWithStatus($method, $url, $body, $progress);

        if ($result['status'] < 200 || $result['status'] >= 300) {
            throw new NetworkException("HTTP {$result['status']} from [{$url}]");
        }

        return $result['body'];
    }

    private function requestWithStatus(string $method, string $url, ?string $body = null, ?callable $progress = null, bool $returnHeaders = false): array
    {
        $currentUrl = $url;
        $hops       = 0;

        while ($hops++ < self::MAX_REDIRECTS) {
            $ch = curl_init($currentUrl);

            if ($ch === false) {
                throw new NetworkException('cURL init failed.');
            }

            $options = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER         => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HTTPHEADER     => $this->buildHeaders($currentUrl),
                CURLOPT_TIMEOUT        => $body !== null ? self::DOWNLOAD_TIMEOUT : self::CONNECT_TIMEOUT,
                CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
                CURLOPT_CUSTOMREQUEST  => $method,
            ];

            if ($progress !== null) {
                $options[CURLOPT_NOPROGRESS]       = false;
                $options[CURLOPT_PROGRESSFUNCTION] = static function (
                    mixed $ch,
                    float|int $dlTotal,
                    float|int $dlNow,
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
            $body_part  = substr($rawStr, $hSize);

            if ($code >= 300 && $code < 400) {
                $location = $this->extractHeader($headerText, 'Location');

                if ($location === null) {
                    throw new NetworkException("Redirect ({$code}) without Location header from [{$currentUrl}].");
                }

                $currentUrl = $this->resolveUrl($currentUrl, $location);
                continue;
            }

            $result = ['status' => $code, 'body' => $body_part];

            if ($returnHeaders) {
                $result['headers'] = $this->parseResponseHeaders($headerText);
            }

            return $result;
        }

        throw new NetworkException("Too many redirects for [{$url}].");
    }

    /** @return list<string> */
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

    /** @return array<string, string> */
    private function parseResponseHeaders(string $headerText): array
    {
        $parsed = [];
        foreach (explode("\r\n", $headerText) as $line) {
            if (!str_contains($line, ':')) {
                continue;
            }
            [$name, $value] = explode(':', $line, 2);
            $parsed[strtolower(trim($name))] = trim($value);
        }
        return $parsed;
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

        while (str_contains($full, '/../')) {
            $full = (string) preg_replace('#/[^/]+/\.\./#', '/', $full, 1);
        }

        return $full;
    }
}
