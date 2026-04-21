<?php declare(strict_types=1);

// ═══════════════════════════════════════════════════════════════════
//  § 6  HttpClient + HttpResponse
// ═══════════════════════════════════════════════════════════════════
final class HttpClient
{
    private string  $method    = 'GET';
    private string  $url       = '';
    private int     $timeout   = 15;
    private bool    $verifySsl = true;
    private ?string $rawBody   = null;

    /** @var array<string, string> */
    private array $headers = [];

    /** @var list<array{expect:int, handler:\Closure():never}> */
    private array $statusHandlers = [];

    /** @var \Closure(HttpResponse): void|null */
    private ?\Closure $onSuccess = null;

    public static function request(): self { return new self(); }

    public function get(string $url): self    { $this->method = 'GET';    $this->url = $url; return $this; }
    public function post(string $url): self   { $this->method = 'POST';   $this->url = $url; return $this; }
    public function put(string $url): self    { $this->method = 'PUT';    $this->url = $url; return $this; }
    public function patch(string $url): self  { $this->method = 'PATCH';  $this->url = $url; return $this; }
    public function delete(string $url): self { $this->method = 'DELETE'; $this->url = $url; return $this; }

    public function jsonBody(array $data): self
    {
        $this->rawBody                 = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $this->headers['Content-Type'] = 'application/json';
        return $this;
    }

    public function formBody(array $data): self
    {
        $this->rawBody                 = http_build_query($data);
        $this->headers['Content-Type'] = 'application/x-www-form-urlencoded';
        return $this;
    }

    public function body(string $raw, string $contentType = 'text/plain'): self
    {
        $this->rawBody                 = $raw;
        $this->headers['Content-Type'] = $contentType;
        return $this;
    }

    public function withHeader(string $name, string $value): self { $this->headers[$name] = $value; return $this; }
    public function bearerToken(string $token): self              { return $this->withHeader('Authorization', 'Bearer ' . $token); }
    public function basicAuth(string $user, string $pass): self   { return $this->withHeader('Authorization', 'Basic ' . base64_encode("{$user}:{$pass}")); }
    public function accept(string $mime): self                    { return $this->withHeader('Accept', $mime); }
    public function timeout(int $seconds): self                   { $this->timeout = max(1, $seconds); return $this; }
    public function withoutSslVerification(): self                { $this->verifySsl = false; return $this; }

    /** @param \Closure():never $handler */
    public function expectStatus(int $expected, \Closure $handler): self
    {
        $this->statusHandlers[] = ['expect' => $expected, 'handler' => $handler];
        return $this;
    }

    /** @param \Closure(HttpResponse): void $callback */
    public function onSuccess(\Closure $callback): self
    {
        $this->onSuccess = $callback;
        return $this;
    }

    public function send(): HttpResponse
    {
        $response = function_exists('curl_init') ? $this->sendViaCurl() : $this->sendViaStream();
        foreach ($this->statusHandlers as $entry) {
            if ($response->status() !== $entry['expect']) {
                ($entry['handler'])();
                exit(1);
            }
        }
        if ($this->onSuccess !== null && $response->successful()) {
            ($this->onSuccess)($response);
        }
        return $response;
    }

    private function sendViaCurl(): HttpResponse
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $this->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CUSTOMREQUEST  => $this->method,
            CURLOPT_HEADER         => true,
            CURLOPT_SSL_VERIFYPEER => $this->verifySsl,
            CURLOPT_SSL_VERIFYHOST => $this->verifySsl ? 2 : 0,
            CURLOPT_USERAGENT      => 'Webkernel-HttpClient/4.0',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
        ]);
        if ($this->rawBody !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->rawBody);
        }
        $headerLines = array_map(
            static fn(string $k, string $v): string => "{$k}: {$v}",
            array_keys($this->headers), $this->headers,
        );
        if ($headerLines !== []) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerLines);
        }
        $raw    = (string) curl_exec($ch);
        $status = (int)    curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $hSize  = (int)    curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $error  = curl_error($ch);
        curl_close($ch);

        return new HttpResponse(
            $status,
            substr($raw, $hSize),
            self::parseRawHeaders(substr($raw, 0, $hSize)),
            $error ?: null,
        );
    }

    private function sendViaStream(): HttpResponse
    {
        $headerLines = array_map(
            static fn(string $k, string $v): string => "{$k}: {$v}",
            array_keys($this->headers), $this->headers,
        );
        $ctx = stream_context_create([
            'http' => [
                'method'          => $this->method,
                'header'          => implode("\r\n", $headerLines),
                'content'         => $this->rawBody ?? '',
                'timeout'         => $this->timeout,
                'follow_location' => 1,
                'max_redirects'   => 5,
                'ignore_errors'   => true,
            ],
            'ssl' => [
                'verify_peer'      => $this->verifySsl,
                'verify_peer_name' => $this->verifySsl,
            ],
        ]);
        $body       = (string) @file_get_contents($this->url, false, $ctx);
        $rawHeaders = $http_response_header ?? [];
        $status     = 0;
        if (isset($rawHeaders[0])) {
            preg_match('#HTTP/[\d.]+\s+(\d+)#', $rawHeaders[0], $m);
            $status = (int) ($m[1] ?? 0);
        }
        return new HttpResponse($status, $body, $rawHeaders);
    }

    /** @return list<string> */
    private static function parseRawHeaders(string $raw): array
    {
        return array_values(array_filter(
            array_map('trim', explode("\r\n", $raw)),
            static fn(string $l) => $l !== '',
        ));
    }
}

final class HttpResponse
{
    /** @param list<string> $headers */
    public function __construct(
        private readonly int     $statusCode,
        private readonly string  $body,
        private readonly array   $headers = [],
        private readonly ?string $error   = null,
    ) {}

    public function status(): int       { return $this->statusCode; }
    public function body(): string      { return $this->body; }
    public function error(): ?string    { return $this->error; }
    public function successful(): bool  { return $this->statusCode >= 200 && $this->statusCode < 300; }
    public function failed(): bool      { return !$this->successful(); }
    public function clientError(): bool { return $this->statusCode >= 400 && $this->statusCode < 500; }
    public function serverError(): bool { return $this->statusCode >= 500; }

    public function json(): mixed
    {
        try {
            return json_decode($this->body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
    }

    public function jsonGet(string $key, mixed $default = null): mixed
    {
        $data = $this->json();
        return is_array($data) ? ($data[$key] ?? $default) : $default;
    }

    public function throwIfFailed(?\Closure $customise = null): self
    {
        if ($this->failed()) {
            if ($customise !== null) {
                $customise($this);
                exit(1);
            }
            EmergencyPageBuilder::create()
                ->title('Upstream Service Error')
                ->message("The remote service returned HTTP {$this->statusCode}.")
                ->severity('CRITICAL')
                ->code(502)
                ->render();
        }
        return $this;
    }
}
