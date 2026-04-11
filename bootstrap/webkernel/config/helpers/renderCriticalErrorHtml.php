<?php
declare(strict_types=1);
/**
 * ═══════════════════════════════════════════════════════════════════
 *  Webkernel — Core Infrastructure
 *  bootstrap/webkernel/config/helpers/renderCriticalErrorHtml.php
 * ═══════════════════════════════════════════════════════════════════
 *
 *  Self-contained pre-boot kernel. No framework, no autoloader,
 *  no query-string routing. Everything lives here.
 *
 *  ┌─────────────────────────────────────────────────────────────┐
 *  │  § 1  HmacSigner          Sign/verify tokens & payloads     │
 *  │  § 2  WebkernelSession    File-backed signed step state      │
 *  │  § 3  WebkernelRouter     /__webkernel-app/* routing         │
 *  │  § 4  EmergencyPageBuilder  Full-page + modal renderer       │
 *  │  § 5  ServerSideValidator   Chainable rule engine            │
 *  │  § 6  HttpClient / HttpResponse                              │
 *  │  § 7  SetupFlow           Declarative first-run wizard       │
 *  │  § 8  Global helpers      webkernel_page(), webkernel_modal()│
 *  └─────────────────────────────────────────────────────────────┘
 *
 * ═══════════════════════════════════════════════════════════════════
 *
 *  SetupFlow — quick reference  (§ 7)
 *  ────────────────────────────────────────────────────────────────
 *
 *  SetupFlow is the single entry-point for any first-run / migration
 *  wizard. It owns token generation, route registration, dispatching,
 *  the complete page, and the fallback redirect — callers never touch
 *  WebkernelRouter or HmacSigner directly.
 *
 *  Minimal usage:
 *
 *    SetupFlow::create(BASE_PATH)
 *        ->fastPath(fn() => is_file($envPath) && is_file($dbPath))
 *        ->guard(fn() => PHP_VERSION_ID >= 80100, 'PHP 8.1+ required')
 *        ->step('Read template',    fn() => readTemplate($state))
 *        ->step('Write .env',       fn() => writeEnv($state))
 *        ->pendingStep('Migrations (deferred to boot)')
 *        ->completePage(
 *            title:   'Setup Complete',
 *            message: '<b>Ready.</b> Click below to open the app.',
 *        )
 *        ->redirectThenTo('/')
 *        ->run();
 *
 *  Guards vs Gaps:
 *
 *    ->guard(fn, message, severity)
 *        Checked BEFORE any route is registered. If the closure
 *        returns false, an error page is rendered immediately and
 *        execution stops. Use for hard server pre-conditions.
 *
 *    ->gap(fn)
 *        A silent side-effect closure injected between steps at
 *        execution time (chmod, mkdir, log, notify…). Runs during
 *        the /run route. Failures are swallowed unless you throw.
 *
 *    ->fastPath(fn)
 *        If the closure returns true, setup_env returns immediately
 *        — Laravel boots normally. The wizard is never shown.
 *
 *  Complete page:
 *
 *    ->completePage(title, message)
 *        Defines the success page shown after all steps pass.
 *        Built-in — no manual route needed.
 *
 *    ->redirectThenTo(url)
 *        The URL of the "Open Application" button on the complete
 *        page (default: '/').
 *
 *  Token lifecycle:
 *    Tokens are stored in {basePath}/.deployment_setup_token.
 *    They are HMAC-signed, expire after 24 h, and are deleted on
 *    successful completion. A new token is issued automatically.
 *
 * ═══════════════════════════════════════════════════════════════════
 */

// ═══════════════════════════════════════════════════════════════════
//  § 1  HmacSigner
// ═══════════════════════════════════════════════════════════════════
final class HmacSigner
{
    private string $secret;
    private string $algo;

    public function __construct(string $secret, string $algo = 'sha256')
    {
        if ($secret === '') {
            throw new \InvalidArgumentException('HmacSigner secret must not be empty.');
        }
        $this->secret = $secret;
        $this->algo   = $algo;
    }

    /**
     * Derive a self-contained opaque token from arbitrary context data.
     * The token is URL-safe base64, unguessable, tied to this server's secret.
     */
    public function token(string ...$parts): string
    {
        /** @disregard */
        $payload = implode('|', $parts) . '|' . microtime(true) . '|' . random_int(0, PHP_INT_MAX);
        $raw     = hash_hmac($this->algo, $payload, $this->secret, true);
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    /** Sign a payload string. Returns "payload.signature". */
    public function sign(string $payload): string
    {
        $sig = hash_hmac($this->algo, $payload, $this->secret);
        return $payload . '.' . $sig;
    }

    /** Verify and extract payload from a signed string. Returns null on tamper. */
    public function verify(string $signed): ?string
    {
        $pos = strrpos($signed, '.');
        if ($pos === false) {
            return null;
        }
        $payload  = substr($signed, 0, $pos);
        $sig      = substr($signed, $pos + 1);
        $expected = hash_hmac($this->algo, $payload, $this->secret);
        return hash_equals($expected, $sig) ? $payload : null;
    }

    /** Sign an arbitrary array as JSON. Returns opaque signed blob. */
    public function signArray(array $data): string
    {
        return $this->sign(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    /** Verify and decode a signed JSON array. Returns null on tamper/invalid. */
    public function verifyArray(string $signed): ?array
    {
        $json = $this->verify($signed);
        if ($json === null) {
            return null;
        }
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : null;
        } catch (\JsonException) {
            return null;
        }
    }

    /** Compute a bare HMAC hex string for arbitrary data. */
    public function compute(string $data): string
    {
        return hash_hmac($this->algo, $data, $this->secret);
    }
}

// ═══════════════════════════════════════════════════════════════════
//  § 2  WebkernelSession
// ═══════════════════════════════════════════════════════════════════
/**
 * Lightweight file-backed session for pre-boot flows.
 * State is stored as a HMAC-signed JSON file in the system temp
 * directory. No cookies, no PHP session.
 */
final class WebkernelSession
{
    private array $data  = [];
    private bool  $dirty = false;

    private function __construct(
        private readonly string     $token,
        private readonly HmacSigner $signer,
        private readonly string     $storePath,
    ) {}

    public static function load(string $token, HmacSigner $signer, ?string $storeDir = null): self
    {
        $dir      = ($storeDir ?? sys_get_temp_dir()) . '/webkernel_sessions';
        $path     = $dir . '/' . preg_replace('/[^a-zA-Z0-9\-_]/', '', $token) . '.sess';
        $instance = new self($token, $signer, $path);

        if (is_file($path)) {
            $raw  = @file_get_contents($path);
            $data = ($raw !== false) ? $signer->verifyArray($raw) : null;
            if (is_array($data)) {
                if (isset($data['__ts']) && (time() - $data['__ts']) < 7200) {
                    $instance->data = $data;
                } else {
                    @unlink($path);
                }
            }
        }
        return $instance;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): self
    {
        $this->data[$key] = $value;
        $this->dirty      = true;
        return $this;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function save(): bool
    {
        if (!$this->dirty) {
            return true;
        }
        $dir = dirname($this->storePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        $this->data['__ts'] = time();
        $signed = $this->signer->signArray($this->data);
        return @file_put_contents($this->storePath, $signed, LOCK_EX) !== false;
    }

    public function destroy(): void
    {
        @unlink($this->storePath);
    }

    public function token(): string
    {
        return $this->token;
    }
}

// ═══════════════════════════════════════════════════════════════════
//  § 3  WebkernelRouter
// ═══════════════════════════════════════════════════════════════════
/**
 * Internal router for /__webkernel-app/* paths.
 *
 * If the current request matches a registered Webkernel route,
 * the handler is invoked and execution terminates.
 * If no route matches, dispatch() returns false so the framework
 * (Laravel, etc.) can take over.
 */
final class WebkernelRouter
{
    private const PREFIX = '/__webkernel-app/';

    /** @var list<array{pattern: string, handler: \Closure}> */
    private static array $routes = [];

    /** @param \Closure(array<string,string> $params): void $handler */
    public static function register(string $pattern, \Closure $handler): void
    {
        self::$routes[] = ['pattern' => $pattern, 'handler' => $handler];
    }

    /**
     * Attempt to dispatch the current request.
     * Returns true if a route matched. Returns false if no match.
     */
    public static function dispatch(): bool
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $uri = '/' . ltrim($uri, '/');

        if (!str_starts_with($uri, self::PREFIX) && $uri !== rtrim(self::PREFIX, '/')) {
            return false;
        }

        $relative = substr($uri, strlen(self::PREFIX));

        foreach (self::$routes as $route) {
            $params = self::match($route['pattern'], $relative);
            if ($params !== null) {
                ($route['handler'])($params);
                return true;
            }
        }
        return false;
    }

    /** Generate a canonical /__webkernel-app/ URL. */
    public static function url(string $pattern, array $params = []): string
    {
        $path = $pattern;
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', rawurlencode((string) $value), $path);
        }
        return self::PREFIX . ltrim($path, '/');
    }

    /** @return array<string,string>|null */
    private static function match(string $pattern, string $uri): ?array
    {
        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            static fn(array $m): string => '(?P<' . $m[1] . '>[^/]+)',
            $pattern,
        );
        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $uri, $matches)) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }
        return $params;
    }
}

// ═══════════════════════════════════════════════════════════════════
//  § 4  EmergencyPageBuilder
// ═══════════════════════════════════════════════════════════════════
final class EmergencyPageBuilder
{
    // ── Mode ──────────────────────────────────────────────────────
    private bool $isModal = false;

    // ── Page identity ─────────────────────────────────────────────
    private string  $title         = 'System Error';
    private string  $message       = '';
    private int     $code          = 500;
    private string  $severity      = 'CRITICAL';
    private string  $systemState   = '';
    private string  $footerMessage = '';
    private ?string $logBasePath   = null;

    // ── HTTP / HMAC capabilities ──────────────────────────────────
    private ?HmacSigner $hmacSigner = null;

    // ── Canonical URL ─────────────────────────────────────────────
    private ?string $canonicalUrl = null;

    // ── Components ────────────────────────────────────────────────
    /** @var list<array{text:string, href:string, extraCss:string}> */
    private array $buttons = [];

    /** @var list<string> */
    private array $htmlComponents = [];

    // ── Modal buttons ─────────────────────────────────────────────
    /** @var list<array{text:string, href:string, style:'default'|'cancel'|'destructive'}> */
    private array $modalButtons = [];

    // ── Guards ────────────────────────────────────────────────────
    /** @var list<\Closure():bool> */
    private array $guards = [];

    // ── Steps ─────────────────────────────────────────────────────
    /** @var list<array{label:string, closure:\Closure|null, pending:bool}> */
    private array $steps = [];

    /** @var array{label:string, href:string, extraCss:string}|null */
    private ?array $submitStep = null;

    // ── Logos ─────────────────────────────────────────────────────
    private ?string $logoLight = null;
    private ?string $logoDark  = null;

    // ── Factory ───────────────────────────────────────────────────
    public static function create(): self { return new self(); }

    public static function modal(): self
    {
        $instance          = new self();
        $instance->isModal = true;
        return $instance;
    }

    // ── Fluent setters ────────────────────────────────────────────
    public function title(string $title): self          { $this->title         = $title;               return $this; }
    public function message(string $message): self      { $this->message       = $message;             return $this; }
    public function code(int $code): self               { $this->code          = $code;                return $this; }
    public function severity(string $severity): self    { $this->severity      = strtoupper(trim($severity)); return $this; }
    public function systemState(string $state): self    { $this->systemState   = $state;               return $this; }
    public function footer(string $footer): self        { $this->footerMessage = $footer;              return $this; }
    public function logTo(?string $basePath): self      { $this->logBasePath   = $basePath;            return $this; }
    public function canonicalize(string $url): self     { $this->canonicalUrl  = $url;                 return $this; }
    public function withSigner(HmacSigner $signer): self { $this->hmacSigner   = $signer;              return $this; }

    public function hmac(string $data): string
    {
        if ($this->hmacSigner === null) {
            throw new \LogicException('Call withSigner() before hmac().');
        }
        return $this->hmacSigner->compute($data);
    }

    public function sendHttpRequest(): HttpClient { return HttpClient::request(); }

    public function logo(?string $light = null, ?string $dark = null): self
    {
        $this->logoLight = self::resolveLogoSrc($light ?? $dark);
        $this->logoDark  = self::resolveLogoSrc($dark  ?? $light);
        return $this;
    }

    private static function resolveLogoSrc(?string $src): ?string
    {
        if ($src === null) return null;
        if (str_starts_with($src, 'data:')) return $src;
        if (is_file($src)) {
            $mime = match (strtolower(pathinfo($src, PATHINFO_EXTENSION))) {
                'svg'         => 'image/svg+xml',
                'webp'        => 'image/webp',
                'jpg', 'jpeg' => 'image/jpeg',
                default       => 'image/png',
            };
            $raw = @file_get_contents($src);
            if ($raw !== false) {
                return 'data:' . $mime . ';base64,' . base64_encode($raw);
            }
        }
        return $src;
    }

    // ── Component builders ────────────────────────────────────────
    public function addButton(string $text, string $href = '/', string $extraCss = ''): self
    {
        $this->buttons[] = ['text' => $text, 'href' => $href, 'extraCss' => $extraCss];
        return $this;
    }

    public function addHtmlComponent(string $html): self
    {
        $this->htmlComponents[] = $html;
        return $this;
    }

    public function modalButton(string $text, string $href, string $style = 'default'): self
    {
        $this->modalButtons[] = ['text' => $text, 'href' => $href, 'style' => $style];
        return $this;
    }

    // ── Steps ─────────────────────────────────────────────────────
    /** @param (\Closure():bool)|(\Closure():string)|null $closure */
    public function step(string $label, ?\Closure $closure = null, bool $pending = false): self
    {
        $this->steps[] = ['label' => $label, 'closure' => $closure, 'pending' => $pending];
        return $this;
    }

    public function submitStep(string $label, string $href = '/', string $extraCss = ''): self
    {
        $this->submitStep = ['label' => $label, 'href' => $href, 'extraCss' => $extraCss];
        return $this;
    }

    // ── Guards ────────────────────────────────────────────────────
    /** @param \Closure():bool $check */
    public function guard(\Closure $check): self
    {
        $this->guards[] = $check;
        return $this;
    }

    public function renderIfGuardFails(): self
    {
        foreach ($this->guards as $guard) {
            if (!$guard()) {
                $this->render();
            }
        }
        return $this;
    }

    // ── Semantic presets ──────────────────────────────────────────
    public function validationFailed(string $detail, int $code = 422): self
    {
        return $this->title('Validation Failed')->message($detail)->severity('WARNING')->code($code);
    }

    public function accessBlocked(string $reason, string $supportHref = ''): self
    {
        $builder = $this->title('Access Blocked')->message($reason)->severity('WARNING')->code(403);
        if ($supportHref !== '') {
            $builder->addButton('Contact Support', $supportHref);
        }
        return $builder;
    }

    public function rateLimited(string $detail = 'Please slow down and try again shortly.'): self
    {
        return $this->title('Too Many Requests')->message($detail)->severity('WARNING')->code(429)->footer('RATE LIMIT REACHED');
    }

    public function maintenance(string $detail = 'The system is being updated. Please check back shortly.'): self
    {
        return $this->title('Scheduled Maintenance')->message($detail)->severity('INFO')->code(503);
    }

    // ── Static inline submit-gating ───────────────────────────────
    /** @param list<\Closure():bool> $checks */
    public static function gatedSubmitButton(
        string $label,
        array  $checks,
        string $accent   = '#3b82f6',
        string $name     = 'submit',
        string $value    = '1',
        string $extraCss = '',
    ): string {
        foreach ($checks as $check) {
            if (!$check()) {
                return sprintf(
                    '<button type="button" disabled'
                    . ' style="margin-top:1rem;padding:.55rem 1.3rem;background:transparent;'
                    . 'border:1px solid #2a2a2a;color:#444;font-family:inherit;font-size:.72rem;'
                    . 'font-weight:600;text-transform:uppercase;letter-spacing:.08em;'
                    . 'cursor:not-allowed;%s" title="Action not permitted at this time">'
                    . '%s — NOT PERMITTED'
                    . '</button>',
                    htmlspecialchars($extraCss, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                    htmlspecialchars($label,    ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                );
            }
        }
        return sprintf(
            '<button type="submit" name="%s" value="%s"'
            . ' style="margin-top:1rem;padding:.55rem 1.3rem;background:transparent;'
            . 'border:1px solid %s;color:%s;font-family:inherit;font-size:.72rem;'
            . 'font-weight:600;text-transform:uppercase;letter-spacing:.08em;cursor:pointer;%s">'
            . '%s</button>',
            htmlspecialchars($name,     ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($value,    ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($accent,   ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($accent,   ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($extraCss, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($label,    ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        );
    }

    // ── Modal render ──────────────────────────────────────────────
    public function renderModal(): string
    {
        [$accent, $accentDim, , , , $iconSvg] = self::palette($this->severity);

        $eTitle   = htmlspecialchars($this->title,   ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $msgBlock = $this->message !== ''
            ? htmlspecialchars($this->message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            : '';

        $buttons = '';
        foreach ($this->modalButtons as $btn) {
            $borderColor = match ($btn['style']) {
                'destructive' => '#ff3333',
                'cancel'      => '#444',
                default       => $accent,
            };
            $textColor = match ($btn['style']) {
                'destructive' => '#ff3333',
                'cancel'      => '#888',
                default       => $accent,
            };
            $buttons .= sprintf(
                '<a href="%s" style="display:inline-flex;align-items:center;justify-content:center;'
                . 'min-width:80px;padding:.45rem 1.1rem;background:transparent;border:1px solid %s;'
                . 'color:%s;font-family:inherit;font-size:.72rem;font-weight:600;'
                . 'text-transform:uppercase;letter-spacing:.07em;text-decoration:none;'
                . 'border-radius:4px;transition:background .12s;cursor:pointer;">%s</a>',
                htmlspecialchars($btn['href'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                $borderColor, $textColor,
                htmlspecialchars($btn['text'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            );
        }

        if ($buttons === '') {
            $buttons = '<a href="/" style="display:inline-flex;align-items:center;justify-content:center;'
                . 'min-width:80px;padding:.45rem 1.1rem;background:transparent;border:1px solid #444;'
                . 'color:#888;font-family:inherit;font-size:.72rem;font-weight:600;'
                . 'text-transform:uppercase;letter-spacing:.07em;text-decoration:none;'
                . 'border-radius:4px;">OK</a>';
        }

        return <<<HTML
<div class="webkernel-components-modal-overlay" style="
    position:fixed;inset:0;z-index:9999;
    display:flex;align-items:center;justify-content:center;
    background:rgba(0,0,0,.6);
    backdrop-filter:blur(4px);
    -webkit-backdrop-filter:blur(4px);
    padding:1rem;
    font-family:'Space Grotesk',system-ui,sans-serif;
">
  <div class="webkernel-components-modal" role="alertdialog" aria-modal="true" aria-labelledby="webkernel-components-modal-title" style="
      background:#111;
      border:1px solid #222;
      border-radius:12px;
      width:100%;max-width:380px;
      padding:1.5rem 1.5rem 1.25rem;
      box-shadow:0 20px 60px rgba(0,0,0,.8);
      text-align:center;
  ">
    <div style="width:36px;height:36px;margin:0 auto .85rem;opacity:.9">{$iconSvg}</div>
    <div id="webkernel-components-modal-title" style="font-size:.9rem;font-weight:700;color:#fff;letter-spacing:.02em;margin-bottom:.5rem;">{$eTitle}</div>
    <div style="font-size:.78rem;color:#aaa;line-height:1.55;margin-bottom:1.25rem;">{$msgBlock}</div>
    <div style="display:flex;gap:.6rem;justify-content:center;flex-wrap:wrap;">{$buttons}</div>
  </div>
</div>
HTML;
    }

    // ── Full-page render ──────────────────────────────────────────
    public function render(): never
    {
        if ($this->isModal) {
            echo $this->renderModal();
            exit(0);
        }

        $incidentId = 'INC-' . strtoupper(
            substr(hash('sha256', $this->message . microtime(true)), 0, 7)
        );
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');

        if ($this->logBasePath !== null) {
            self::writeIncidentLog(
                $incidentId, $this->severity, $this->title,
                $this->message, $this->code, $this->logBasePath,
            );
        }

        // ── CLI path ──────────────────────────────────────────────
        if (PHP_SAPI === 'cli') {
            $state = $this->systemState !== '' ? $this->systemState : 'SYSTEM STATE: ' . $this->severity;
            fwrite(STDERR, sprintf(
                "%s\nINCIDENT : %s\nSEVERITY : %s\nTIMESTAMP: %s\n\n%s\n%s\n",
                $state, $incidentId, $this->severity, $timestamp,
                strtoupper($this->title), $this->message,
            ));
            throw new \RuntimeException($this->message, $this->code);
        }

        // ── Palette ───────────────────────────────────────────────
        [$accent, $accentDim, $accentBorder, $defaultState, $defaultFooter, $iconSvg]
            = self::palette($this->severity);

        $resolvedState  = $this->systemState   !== '' ? $this->systemState   : $defaultState;
        $resolvedFooter = $this->footerMessage !== '' ? $this->footerMessage : $defaultFooter;

        if ($this->canonicalUrl !== null) {
            header('X-Canonical-Url: ' . $this->canonicalUrl);
        }

        // ── Execute steps → build step HTML ──────────────────────
        $stepsHtml = '';
        $allPassed = true;

        if ($this->steps !== []) {
            $stepsHtml .= '<div class="steps">';
            foreach ($this->steps as $step) {
                ['label' => $label, 'closure' => $closure, 'pending' => $pending] = $step;
                $eLabel = htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                if ($pending || $closure === null) {
                    $stepsHtml .= self::stepRow($eLabel, 'pending', '', $accent);
                    continue;
                }

                try {
                    $result = $closure();
                } catch (\Throwable $e) {
                    $result = $e->getMessage();
                }

                if ($result === true) {
                    $stepsHtml .= self::stepRow($eLabel, 'ok', '', $accent);
                } else {
                    $allPassed = false;
                    $eDetail   = htmlspecialchars(
                        is_string($result) ? $result : '',
                        ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8',
                    );
                    $stepsHtml .= self::stepRow($eLabel, 'fail', $eDetail, $accent);
                }
            }

            if ($this->submitStep !== null) {
                if ($allPassed) {
                    $eLabel    = htmlspecialchars($this->submitStep['label'],    ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $eHref     = htmlspecialchars($this->submitStep['href'],     ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $eExtraCss = htmlspecialchars($this->submitStep['extraCss'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $stepsHtml .= sprintf(
                        '<div class="submit-row">'
                        . '<a href="%s" class="proceed-btn" style="border-color:%s;color:%s;%s">%s</a>'
                        . '</div>',
                        $eHref, $accent, $accent, $eExtraCss, $eLabel,
                    );
                } else {
                    $stepsHtml .= '<div class="submit-row submit-blocked">'
                        . 'Setup incomplete — correct the errors above before continuing.'
                        . '</div>';
                }
            }

            $stepsHtml .= '</div>';
        }

        // ── Assemble & emit ───────────────────────────────────────
        $eSeverity = htmlspecialchars($this->severity,  ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $eTitle    = htmlspecialchars($this->title,     ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $eState    = htmlspecialchars($resolvedState,   ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $eFooter   = htmlspecialchars($resolvedFooter,  ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $buttonsHtml = '';
        foreach ($this->buttons as $btn) {
            $buttonsHtml .= sprintf(
                '<a href="%s" class="action-btn" style="border-color:%s;color:%s;%s">%s</a>',
                htmlspecialchars($btn['href'],     ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                $accent, $accent,
                htmlspecialchars($btn['extraCss'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($btn['text'],     ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            );
        }

        $extraHtml = implode("\n", $this->htmlComponents);

        $msgBlock = $this->message !== ''
            ? "<div class=\"msg-block\">{$this->message}</div>"
            : '';

        $logoHtml     = $this->buildLogoHtml();
        $canonicalTag = $this->canonicalUrl !== null
            ? '<link rel="canonical" href="' . htmlspecialchars($this->canonicalUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"/>'
            : '';

        http_response_code($this->code);
        echo self::buildDocument(
            eState: $eState, eTitle: $eTitle, eSeverity: $eSeverity,
            eFooter: $eFooter, incidentId: $incidentId, timestamp: $timestamp,
            accent: $accent, accentDim: $accentDim, accentBorder: $accentBorder,
            iconSvg: $iconSvg, msgBlock: $msgBlock, stepsHtml: $stepsHtml,
            extraHtml: $extraHtml, buttonsHtml: $buttonsHtml,
            logoHtml: $logoHtml, canonicalTag: $canonicalTag,
        );
        exit(1);
    }

    // ── Internal helpers ──────────────────────────────────────────
    private function buildLogoHtml(): string
    {
        if ($this->logoLight === null && $this->logoDark === null) {
            return <<<'HTML'
<picture>
  <source srcset="/logo-dark.png" media="(prefers-color-scheme:dark)"/>
  <img src="/logo-light.png" alt="System" loading="eager" style="max-width:160px;width:100%;height:auto;display:block;margin:0 auto .85rem;opacity:.85"/>
</picture>
HTML;
        }

        $light = $this->logoLight ?? $this->logoDark ?? '';
        $dark  = $this->logoDark  ?? $this->logoLight ?? '';

        return sprintf(
            '<picture>'
            . '<source srcset="%s" media="(prefers-color-scheme:dark)"/>'
            . '<img src="%s" alt="System" loading="eager" style="max-width:160px;width:100%%;height:auto;display:block;margin:0 auto .85rem;opacity:.85"/>'
            . '</picture>',
            htmlspecialchars($dark,  ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($light, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        );
    }

    private static function stepRow(string $eLabel, string $status, string $eDetail, string $accent): string
    {
        [$icon, $color] = match ($status) {
            'ok'    => ['✓', $accent],
            'fail'  => ['✕', '#ff3333'],
            default => ['⋯', '#555'],
        };
        $detail = $eDetail !== '' ? "<span class=\"step-detail\">{$eDetail}</span>" : '';
        return sprintf(
            '<div class="step step-%s">'
            . '<span class="step-icon" style="color:%s">%s</span>'
            . '<span class="step-label">%s%s</span>'
            . '</div>',
            htmlspecialchars($status, ENT_QUOTES, 'UTF-8'),
            $color, $icon, $eLabel, $detail,
        );
    }

    /**
     * @return array{string, string, string, string, string, string}
     *         [accent, accentDim, accentBorder, defaultState, defaultFooter, iconSvg]
     */
    public static function palette(string $sev): array
    {
        return match ($sev) {
            'INFO', 'SETUP' => [
                '#3b82f6',
                'rgba(59,130,246,.1)',
                '#3b82f6',
                'SETTING UP YOUR ENVIRONMENT',
                'PLEASE WAIT — SETUP IN PROGRESS',
                '<svg viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
            ],
            'WARNING' => [
                '#f59e0b',
                'rgba(245,158,11,.1)',
                '#f59e0b',
                'SYSTEM WARNING',
                'ACTION MAY BE REQUIRED',
                '<svg viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
            ],
            default => [
                '#ff3333',
                'rgba(255,0,0,.08)',
                '#ff3333',
                'SYSTEM STATE: SEALED',
                'NO FURTHER ACTION IS PERMITTED',
                '<svg viewBox="0 0 24 24" fill="none" stroke="#ff3333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
            ],
        };
    }

    private static function buildDocument(
        string $eState,
        string $eTitle,
        string $eSeverity,
        string $eFooter,
        string $incidentId,
        string $timestamp,
        string $accent,
        string $accentDim,
        string $accentBorder,
        string $iconSvg,
        string $msgBlock,
        string $stepsHtml,
        string $extraHtml,
        string $buttonsHtml,
        string $logoHtml,
        string $canonicalTag,
    ): string {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <meta name="robots" content="noindex,nofollow"/>
  {$canonicalTag}
  <title>{$eState}</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
    ::selection, ::-moz-selection { background: transparent; }
    html, body { user-select: none; -webkit-user-select: none; pointer-events: none; }
    a, button { pointer-events: all !important; }
    body {
      font-family: 'Space Grotesk', system-ui, sans-serif;
      background: #000;
      color: #d0d0d0;
      min-height: 100dvh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: .75rem;
    }
    .card {
      max-width: 680px; width: 100%;
      background: #0d0d0d; border: 1px solid #1a1a1a;
      overflow: hidden; box-shadow: 0 4px 32px rgba(0,0,0,.85);
    }
    .card-header {
      background: #080808; border-bottom: 1px solid #1a1a1a;
      padding: .65rem 1rem; display: flex; align-items: center; gap: .75rem;
    }
    .header-state {
      flex: 1; font-size: .68rem; font-weight: 600;
      text-transform: uppercase; letter-spacing: .08em; color: #fff;
    }
    .header-incident {
      flex: 1; text-align: center; color: #555; font-size: .65rem;
      font-weight: 600; text-transform: uppercase; letter-spacing: .08em;
      font-family: 'Courier New', monospace;
    }
    .header-severity {
      flex: 1; display: flex; align-items: center;
      justify-content: flex-end; gap: .4rem;
    }
    .severity-icon { width: 15px; height: 15px; flex-shrink: 0; }
    .severity-icon svg { width: 100%; height: 100%; display: block; }
    .severity-label {
      color: {$accent}; font-weight: 700; font-size: .68rem;
      text-transform: uppercase; letter-spacing: .08em;
    }
    .card-body { padding: 1.25rem 1rem; }
    .identity { margin-bottom: 1rem; text-align: center; }
    .incident-title {
      font-size: .8rem; font-weight: 600; color: {$accent};
      text-transform: uppercase; letter-spacing: .1em;
    }
    .msg-block {
      background: {$accentDim}; border-left: 2px solid {$accentBorder};
      padding: .8rem .9rem; margin: .85rem 0; font-size: .8rem;
      line-height: 1.6; white-space: pre-wrap; word-break: break-word; color: #d0d0d0;
    }
    .steps { margin: .85rem 0; display: flex; flex-direction: column; gap: .3rem; }
    .step { display: flex; align-items: flex-start; gap: .55rem; font-size: .78rem; padding: .4rem .45rem; border-radius: 2px; }
    .step-ok      { background: rgba(59,130,246,.04); }
    .step-fail    { background: rgba(255,51,51,.05); }
    .step-pending { background: transparent; }
    .step-icon { font-family: monospace; font-size: .82rem; line-height: 1.35; flex-shrink: 0; width: 1rem; text-align: center; }
    .step-label  { color: #bbb; line-height: 1.4; }
    .step-detail { display: block; margin-top: .2rem; font-size: .69rem; color: #ff5555; font-family: 'Courier New', monospace; }
    .submit-row { margin-top: 1rem; padding-top: .85rem; border-top: 1px solid #1e1e1e; }
    .submit-blocked { font-size: .7rem; color: #444; text-transform: uppercase; letter-spacing: .06em; }
    .proceed-btn {
      display: inline-block; padding: .55rem 1.4rem; background: transparent;
      border: 1px solid {$accent}; color: {$accent}; font-family: inherit;
      font-size: .73rem; font-weight: 600; text-transform: uppercase;
      letter-spacing: .08em; text-decoration: none; transition: background .14s;
    }
    .proceed-btn:hover { background: {$accentDim}; }
    .actions { display: flex; flex-wrap: wrap; gap: .5rem; margin-top: .85rem; }
    .action-btn {
      display: inline-block; padding: .5rem 1.2rem; background: transparent;
      border: 1px solid; font-family: inherit; font-size: .72rem; font-weight: 600;
      text-transform: uppercase; letter-spacing: .08em; text-decoration: none; transition: background .14s;
    }
    .action-btn:hover { background: {$accentDim}; }
    .card-footer {
      padding: .65rem 1rem; text-align: center; font-size: .65rem;
      color: #555; text-transform: uppercase; letter-spacing: .08em;
      background: #080808; border-top: 1px solid #1a1a1a;
    }
    .timestamp-bar {
      margin-top: 1.25rem; text-align: center; font-size: .62rem;
      color: rgba(255,255,255,.25); font-family: 'Courier New', monospace; letter-spacing: .05em;
    }
    @media (max-width: 480px) {
      .card-body { padding: .9rem .75rem; }
      .msg-block { font-size: .74rem; padding: .65rem .75rem; }
      .card-footer { font-size: .6rem; }
      .timestamp-bar { font-size: .57rem; margin-top: .85rem; }
      .header-incident { display: none; }
      .step { font-size: .74rem; }
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="card-header">
      <div class="header-state">{$eState}</div>
      <div class="header-incident">{$incidentId}</div>
      <div class="header-severity">
        <span class="severity-icon">{$iconSvg}</span>
        <span class="severity-label">{$eSeverity}</span>
      </div>
    </div>
    <div class="card-body">
      <div class="identity">
        {$logoHtml}
        <div class="incident-title">{$eTitle}</div>
      </div>
      {$msgBlock}
      {$stepsHtml}
      {$extraHtml}
      <div class="actions">{$buttonsHtml}</div>
    </div>
    <div class="card-footer">{$eFooter}</div>
  </div>
  <div class="timestamp-bar">TIMESTAMP (UTC): {$timestamp}</div>
</body>
</html>
HTML;
    }

    private static function writeIncidentLog(
        string $incidentId,
        string $severity,
        string $title,
        string $message,
        int    $code,
        string $basePath,
    ): void {
        $logDir = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $entry = sprintf(
            "[%s] INCIDENT:%s | SEV:%s | CODE:%d | TITLE:%s | MSG:%s | UA:%s | IP:%s\n",
            gmdate('Y-m-d\TH:i:s\Z'),
            $incidentId, $severity, $code,
            str_replace(["\r", "\n"], ' ', $title),
            str_replace(["\r", "\n"], ' ', $message),
            $_SERVER['HTTP_USER_AGENT'] ?? 'CLI',
            $_SERVER['REMOTE_ADDR']     ?? 'INTERNAL',
        );
        @file_put_contents("{$logDir}/critical-incidents.log", $entry, FILE_APPEND | LOCK_EX);
    }
}

// ═══════════════════════════════════════════════════════════════════
//  § 5  ServerSideValidator
// ═══════════════════════════════════════════════════════════════════
/**
 * Chainable server-side validator.
 *
 * String rules: required | email | url | numeric | integer
 *   min:N | max:N | min_value:N | max_value:N
 *   in:a,b,c | not_in:a,b,c | regex:/pattern/
 *
 * Closure rules receive ($value, $fullDataArray) → true|false|string.
 */
final class ServerSideValidator
{
    /** @var array<string, mixed> */
    private array $data;

    /** @var list<array{field:string, rule:string|\Closure, label:string}> */
    private array $rules    = [];

    /** @var list<string> */
    private array $errors   = [];

    private bool $evaluated = false;

    private function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function check(array $data): self { return new self($data); }

    /** @param string|\Closure(mixed, array<string,mixed>): (bool|string) $rule */
    public function field(string $field, string|\Closure $rule, string $label = ''): self
    {
        $this->rules[] = [
            'field' => $field,
            'rule'  => $rule,
            'label' => $label !== '' ? $label : ucfirst(str_replace('_', ' ', $field)),
        ];
        return $this;
    }

    public function evaluate(): self
    {
        if ($this->evaluated) return $this;
        $this->evaluated = true;

        foreach ($this->rules as $entry) {
            ['field' => $field, 'label' => $label, 'rule' => $rule] = $entry;
            $value = $this->data[$field] ?? null;

            if ($rule instanceof \Closure) {
                $result = $rule($value, $this->data);
                if ($result !== true) {
                    $this->errors[] = is_string($result) ? $result : "'{$label}' failed validation.";
                }
                continue;
            }

            foreach (explode('|', $rule) as $token) {
                $error = self::applyToken($token, $label, $value);
                if ($error !== null) {
                    $this->errors[] = $error;
                    break;
                }
            }
        }
        return $this;
    }

    public function passes(): bool        { return $this->evaluate()->errors === []; }
    public function fails(): bool         { return !$this->passes(); }
    /** @return list<string> */
    public function errors(): array       { return $this->evaluate()->errors; }
    public function firstError(): string  { return $this->evaluate()->errors[0] ?? ''; }

    /** @param \Closure(string, list<string>): void $callback */
    public function onFail(\Closure $callback): self
    {
        if ($this->fails()) $callback($this->firstError(), $this->errors());
        return $this;
    }

    /** @param \Closure(EmergencyPageBuilder, string): void|null $customise */
    public function renderOnFail(?\Closure $customise = null): self
    {
        if ($this->fails()) {
            $builder = EmergencyPageBuilder::create()->validationFailed($this->firstError());
            if ($customise !== null) $customise($builder, $this->firstError());
            $builder->render();
        }
        return $this;
    }

    private static function applyToken(string $token, string $label, mixed $value): ?string
    {
        $str = is_string($value) ? trim($value) : (string) ($value ?? '');
        [$name, $param] = str_contains($token, ':') ? explode(':', $token, 2) : [$token, ''];

        return match ($name) {
            'required'  => ($value === null || $value === '' || $value === []) ? "'{$label}' is required." : null,
            'email'     => filter_var($value, FILTER_VALIDATE_EMAIL) === false  ? "'{$label}' must be a valid email address." : null,
            'url'       => filter_var($value, FILTER_VALIDATE_URL) === false    ? "'{$label}' must be a valid URL." : null,
            'numeric'   => !is_numeric($value)                                  ? "'{$label}' must be numeric." : null,
            'integer'   => filter_var($value, FILTER_VALIDATE_INT) === false    ? "'{$label}' must be an integer." : null,
            'min'       => mb_strlen($str) < (int) $param                       ? "'{$label}' must be at least {$param} characters." : null,
            'max'       => mb_strlen($str) > (int) $param                       ? "'{$label}' must not exceed {$param} characters." : null,
            'min_value' => (float) $value < (float) $param                      ? "'{$label}' must be at least {$param}." : null,
            'max_value' => (float) $value > (float) $param                      ? "'{$label}' must not exceed {$param}." : null,
            'in'        => !in_array($str, explode(',', $param), true)           ? "'{$label}' contains an unacceptable value." : null,
            'not_in'    => in_array($str, explode(',', $param), true)            ? "'{$label}' contains a disallowed value." : null,
            'regex'     => !preg_match($param, $str)                             ? "'{$label}' format is invalid." : null,
            default     => null,
        };
    }
}

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

// ═══════════════════════════════════════════════════════════════════
//  § 7  SetupFlow
// ═══════════════════════════════════════════════════════════════════
/**
 * Declarative first-run wizard.
 *
 * Owns everything: token lifecycle, route registration, page rendering,
 * the complete page, and the fallback redirect. Callers never touch
 * WebkernelRouter or HmacSigner directly.
 *
 * ┌──────────────────────────────────────────────────────────────┐
 * │  Three kinds of pre-conditions                               │
 * │                                                              │
 * │  fastPath(fn)  → if fn() === true, return immediately.      │
 * │                  Laravel boots normally. Wizard never shown. │
 * │                                                              │
 * │  guard(fn, msg, sev)  → if fn() === false, render an error  │
 * │                         page immediately and terminate.      │
 * │                         Use for hard server pre-conditions   │
 * │                         (PHP version, extensions, perms…).  │
 * │                                                              │
 * │  gap(fn)  → silent side-effect closure injected between     │
 * │             steps at /run time (chmod, mkdir, log…).        │
 * │             Exceptions bubble up as step failures.          │
 * └──────────────────────────────────────────────────────────────┘
 *
 * Token lifecycle
 * ───────────────
 * Stored in {basePath}/.deployment_setup_token (HMAC-signed JSON).
 * Expire after 24 h. Deleted on successful completion.
 * Auto-regenerated on expiry/tamper/missing.
 *
 * Usage
 * ─────
 *   SetupFlow::create(BASE_PATH)
 *       ->fastPath(fn() => is_file($envPath) && is_file($dbPath))
 *       ->guard(fn() => PHP_VERSION_ID >= 80100, 'PHP 8.1+ required')
 *       ->guard(fn() => is_writable(BASE_PATH),  'Root must be writable')
 *       ->gap(fn() => chmod(BASE_PATH . '/storage', 0775))
 *       ->step('Read template',    fn() => readTemplate($state))
 *       ->step('Generate key',     fn() => generateKey($state))
 *       ->step('Write .env',       fn() => writeEnv($state))
 *       ->step('Create SQLite',    fn() => createDb($state))
 *       ->pendingStep('Migrations (deferred to boot)')
 *       ->completePage(
 *           title:   'Setup Complete',
 *           message: '<b>Ready.</b> Click below to open the app.',
 *       )
 *       ->redirectThenTo('/')
 *       ->run();
 *
 * After ->run() returns (it only returns when no URL matches), Laravel
 * continues its normal boot sequence.
 */
final class SetupFlow
{
    // ── Config ────────────────────────────────────────────────────
    private readonly string $basePath;
    private readonly string $tokenFile;
    private int             $tokenTtl = 86400; // 24 h

    // ── Preview page ──────────────────────────────────────────────
    private string  $previewTitle   = 'First-run Setup Required';
    private string  $previewMessage = '<b>This application has not been initialised yet.</b>'
                                    . ' The following actions will be performed on this server.'
                                    . ' Review them carefully, then click Proceed when ready.';

    // ── Complete page ─────────────────────────────────────────────
    private string $completeTitle   = 'Setup Complete';
    private string $completeMessage = '<b>The environment has been initialised successfully.</b>'
                                    . ' Database migrations will run automatically on first boot.'
                                    . ' Click the button below to open the application.';
    private string $completeButtonLabel = 'Open Application';
    private string $redirectAfterComplete = '/';

    // ── Incomplete (error) page ───────────────────────────────────
    private string $incompleteTitle   = 'Setup Incomplete';
    private string $incompleteMessage = 'One or more setup steps did not complete successfully.'
                                      . ' Check server permissions and try again.';

    // ── Token ─────────────────────────────────────────────────────
    private HmacSigner $signer;
    private string     $token;

    // ── Fast-paths ────────────────────────────────────────────────
    /**
     * @var list<\Closure():bool>
     * If any returns true → return immediately (no wizard).
     */
    private array $fastPaths = [];

    // ── Guards ────────────────────────────────────────────────────
    /**
     * @var list<array{check:\Closure():bool, message:string, severity:string}>
     * If check() returns false → render error page immediately.
     */
    private array $guards = [];

    // ── Steps (shown on preview + run pages) ──────────────────────
    /**
     * @var list<array{
     *   label:   string,
     *   closure: \Closure|null,
     *   pending: bool,
     *   gap:     \Closure|null,
     * }>
     */
    private array $steps = [];

    // ── Logo ──────────────────────────────────────────────────────
    private ?string $logoLight = null;
    private ?string $logoDark  = null;

    // ── Constructor ───────────────────────────────────────────────
    private function __construct(string $basePath)
    {
        $this->basePath  = rtrim($basePath, '/\\');
        $this->tokenFile = $this->basePath . '/.deployment_setup_token';

        //--- Logos Declaration Base64 encoded
        $this->logoLight = webkernelLogoForLightMode();
        $this->logoDark  = webkernelLogoForDarkMode();
    }

    public static function create(string $basePath): self
    {
        return new self($basePath);
    }

    // ── Fluent API ────────────────────────────────────────────────

    /**
     * Fast-path: if the closure returns true, the wizard is skipped
     * entirely and ->run() returns immediately (Laravel boots normally).
     *
     * You may chain multiple fast-paths; any one returning true exits.
     *
     * @param \Closure():bool $check
     */
    public function fastPath(\Closure $check): self
    {
        $this->fastPaths[] = $check;
        return $this;
    }

    /**
     * Guard: if the closure returns false, render an error page immediately.
     *
     * Guards run BEFORE any route is registered and BEFORE any step runs.
     * Use them for hard server pre-conditions that the wizard cannot fix.
     *
     * @param \Closure():bool $check
     * @param string $message  Human-readable description of what failed.
     * @param string $severity EmergencyPageBuilder severity (default CRITICAL).
     */
    public function guard(\Closure $check, string $message = '', string $severity = 'CRITICAL'): self
    {
        $this->guards[] = [
            'check'    => $check,
            'message'  => $message,
            'severity' => $severity,
        ];
        return $this;
    }

    /**
     * Gap: a silent side-effect closure injected BEFORE the next step.
     *
     * Gaps run during the /run route between steps. They are invisible
     * to the user unless they throw an exception (which bubbles up as
     * a step failure on the preceding step's error output).
     *
     * Use for: chmod, mkdir, notify, log, seed data, cleanup, etc.
     *
     * @param \Closure():void $fn
     */
    public function gap(\Closure $fn): self
    {
        // Attach as a gap marker on the last registered step.
        // If no step exists yet, store as a pre-step gap.
        $idx = count($this->steps) - 1;
        if ($idx >= 0) {
            $this->steps[$idx]['gap'] = $fn;
        } else {
            // Pre-step gap: runs before the first step closure.
            $this->steps[] = [
                'label'   => '',    // invisible
                'closure' => null,
                'pending' => false,
                'gap'     => $fn,
                '_pre'    => true,  // internal marker — skip rendering
            ];
        }
        return $this;
    }

    /**
     * Add a visible setup step with an execution closure.
     *
     * The closure must return:
     *   true         → step succeeded
     *   string       → step failed (the string is shown as the error detail)
     *   (throws)     → step failed (exception message is the error detail)
     *
     * @param \Closure():(bool|string) $closure
     */
    public function step(string $label, \Closure $closure): self
    {
        $this->steps[] = [
            'label'   => $label,
            'closure' => $closure,
            'pending' => false,
            'gap'     => null,
            '_pre'    => false,
        ];
        return $this;
    }

    /**
     * Add a pending (display-only) step — shown grayed out on all pages.
     * Use for steps that are deferred (e.g. migrations run at framework boot).
     */
    public function pendingStep(string $label): self
    {
        $this->steps[] = [
            'label'   => $label,
            'closure' => null,
            'pending' => true,
            'gap'     => null,
            '_pre'    => false,
        ];
        return $this;
    }

    /**
     * Customise the preview page (shown before the user clicks "Proceed").
     */
    public function previewPage(string $title, string $message = ''): self
    {
        $this->previewTitle   = $title;
        if ($message !== '') $this->previewMessage = $message;
        return $this;
    }

    /**
     * Define the complete (success) page shown after all steps pass.
     *
     * @param string $title        Page title.
     * @param string $message      HTML message body.
     * @param string $buttonLabel  Label for the CTA button.
     * @param string $redirectTo   URL the button points to (default '/').
     *                             Can also be set separately with ->redirectThenTo().
     */
    public function completePage(
        string $title,
        string $message      = '',
        string $buttonLabel  = 'Open Application',
        string $redirectTo   = '',
    ): self {
        $this->completeTitle       = $title;
        if ($message !== '')     $this->completeMessage      = $message;
        if ($buttonLabel !== '') $this->completeButtonLabel  = $buttonLabel;
        if ($redirectTo  !== '') $this->redirectAfterComplete = $redirectTo;
        return $this;
    }

    /**
     * Set the URL the CTA button points to on the complete page.
     * Convenience alias — identical to passing $redirectTo to completePage().
     * Defaults to '/'.
     */
    public function redirectThenTo(string $url): self
    {
        $this->redirectAfterComplete = $url;
        return $this;
    }

    /**
     * Customise the incomplete (error) page.
     */
    public function incompletePage(string $title, string $message): self
    {
        $this->incompleteTitle   = $title;
        $this->incompleteMessage = $message;
        return $this;
    }

    /**
     * Customise the token TTL in seconds (default 24 h).
     */
    public function tokenTtl(int $seconds): self
    {
        $this->tokenTtl = max(60, $seconds);
        return $this;
    }

    /**
     * Embed logos on all setup pages.
     * Accepts data-URIs, file paths, or URLs — same as EmergencyPageBuilder::logo().
     */
    public function logo(?string $light = null, ?string $dark = null): self
    {
        $this->logoLight = $light;
        $this->logoDark  = $dark;
        return $this;
    }

    // ── run() — the single dispatch entry-point ───────────────────

    /**
     * Execute the setup flow.
     *
     * The URL is inspected FIRST. If the current request is already
     * targeting a webkernel URL (/__webkernel-app/*), we own it
     * unconditionally — fast-paths are skipped, guards still apply,
     * and the response always terminates here (exit). This prevents
     * Laravel from ever seeing setup URLs and returning a 404.
     *
     * If the URL is NOT a webkernel URL:
     *   1. Fast-paths  → if any returns true, return normally (Laravel boots).
     *   2. Guards      → if any fails, render error page + terminate.
     *   3. Resolve token + register routes + dispatch.
     *   4. No match    → redirect to preview page.
     *
     * ->run() only returns (without exit) when a fast-path matched and
     * setup is not needed. In every other case execution terminates here.
     */
    public function run(): void
    {
        // ── 0. Webkernel URL intercept ────────────────────────────
        // If the browser is already on a /__webkernel-app/* URL we MUST
        // handle it — no fast-path shortcut, no framework fallback.
        // This is what happens on /complete after setup succeeds: the
        // fast-path would return true and let Laravel boot, which 404s.
        if (self::isWebkernelRequest()) {
            $this->runGuards();          // hard conditions always apply
            [$this->signer, $this->token] = $this->resolveToken();
            $this->registerRoutes();

            if (WebkernelRouter::dispatch()) {
                exit(0);
            }

            // Webkernel URL but no route matched (mangled path, stale link…).
            // Redirect to the preview page instead of letting Laravel 404.
            $previewUrl = WebkernelRouter::url('setup/{token}', ['token' => $this->token]);
            header('Location: ' . $previewUrl, true, 302);
            exit(0);
        }

        // ── 1. Fast-paths ─────────────────────────────────────────
        // Only reached when the current URL is NOT a webkernel URL.
        foreach ($this->fastPaths as $fp) {
            if ($fp()) {
                return; // Setup not needed — let the framework boot normally.
            }
        }

        // ── 2. Guards ─────────────────────────────────────────────
        $this->runGuards();

        // ── 3. Resolve token + register routes ───────────────────
        [$this->signer, $this->token] = $this->resolveToken();
        $this->registerRoutes();

        // ── 4. Dispatch ───────────────────────────────────────────
        if (WebkernelRouter::dispatch()) {
            exit(0);
        }

        // ── 5. Fallback redirect → preview page ───────────────────
        // Non-webkernel URL, setup needed, no route matched → send the
        // browser to the setup preview page.
        $previewUrl = WebkernelRouter::url('setup/{token}', ['token' => $this->token]);
        header('Location: ' . $previewUrl, true, 302);
        exit(0);
    }

    /**
     * Returns true if the current HTTP request targets a webkernel URL.
     * Used to intercept these requests before fast-paths can skip them.
     */
    private static function isWebkernelRequest(): bool
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $uri = '/' . ltrim($uri, '/');
        return str_starts_with($uri, '/__webkernel-app/');
    }

    /**
     * Run all registered guards. Renders a CRITICAL page and terminates
     * on the first failure. Extracted so both code paths in run() share it.
     */
    private function runGuards(): void
    {
        foreach ($this->guards as $guard) {
            if (!($guard['check'])()) {
                $msg = $guard['message'] !== ''
                    ? $guard['message']
                    : 'A required server condition is not satisfied.';

                $builder = EmergencyPageBuilder::create()
                    ->title('Setup Cannot Proceed')
                    ->message(
                        "A required server condition is not satisfied:\n\n"
                        . "  ✕  {$msg}\n\n"
                        . "Fix the server configuration and reload this page."
                    )
                    ->severity($guard['severity'])
                    ->code(500)
                    ->systemState('ENVIRONMENT ERROR')
                    ->footer('SERVER CONFIGURATION ERROR — SETUP BLOCKED')
                    ->addButton('Reload', '/');

                if ($this->logoLight !== null || $this->logoDark !== null) {
                    $builder->logo($this->logoLight, $this->logoDark);
                }

                $builder->render(); // always terminates (exit inside render)
            }
        }
    }

    // ── Route registration ────────────────────────────────────────

    private function registerRoutes(): void
    {
        $token     = $this->token;
        $signer    = $this->signer;
        $tokenFile = $this->tokenFile;
        $self      = $this;

        // ── GET /__webkernel-app/setup/{token} ────────────────────
        // Preview page — lists what will happen, nothing is executed.
        WebkernelRouter::register(
            'setup/{token}',
            static function (array $params) use ($token, $signer, $self): never {
                if (!hash_equals($token, $params['token'])) {
                    SetupFlow::renderBadToken();
                }
                $runUrl       = WebkernelRouter::url('setup/{token}/run',  ['token' => $token]);
                $canonicalUrl = WebkernelRouter::url('setup/{token}',      ['token' => $token]);

                $builder = EmergencyPageBuilder::create()
                    ->title($self->previewTitle)
                    ->severity('SETUP')
                    ->code(200)
                    ->systemState('FIRST-RUN SETUP')
                    ->canonicalize($canonicalUrl)
                    ->footer('WEBKERNEL — REVIEW AND CONFIRM BEFORE PROCEEDING')
                    ->message($self->previewMessage);

                if ($self->logoLight !== null || $self->logoDark !== null) {
                    $builder->logo($self->logoLight, $self->logoDark);
                }

                // Register all visible steps as pending on the preview page
                foreach ($self->steps as $step) {
                    if ($step['_pre'] ?? false) continue; // skip invisible gaps
                    $builder->step($step['label'], pending: true);
                }

                $builder->submitStep('Proceed with Setup', $runUrl);
                $builder->render();
            },
        );

        // ── GET /__webkernel-app/setup/{token}/run ────────────────
        // Execute page — closures run, files are written.
        WebkernelRouter::register(
            'setup/{token}/run',
            static function (array $params) use ($token, $signer, $self): never {
                if (!hash_equals($token, $params['token'])) {
                    SetupFlow::renderBadToken();
                }
                $completeUrl  = WebkernelRouter::url('setup/{token}/complete', ['token' => $token]);
                $canonicalUrl = WebkernelRouter::url('setup/{token}/run',      ['token' => $token]);

                $builder = EmergencyPageBuilder::create()
                    ->title('Setting Up Your Environment')
                    ->severity('SETUP')
                    ->code(200)
                    ->systemState('SETUP IN PROGRESS')
                    ->canonicalize($canonicalUrl)
                    ->footer('WEBKERNEL — FIRST-RUN SETUP');

                if ($self->logoLight !== null || $self->logoDark !== null) {
                    $builder->logo($self->logoLight, $self->logoDark);
                }

                // Collect pre-step gaps (invisible, run before any step).
                $preGaps = [];
                $visibleSteps = [];
                foreach ($self->steps as $step) {
                    if ($step['_pre'] ?? false) {
                        if ($step['gap'] !== null) {
                            $preGaps[] = $step['gap'];
                        }
                    } else {
                        $visibleSteps[] = $step;
                    }
                }

                // Execute pre-step gaps now, wrapped into the first real step
                // so any failure surfaces visibly. If there are no visible steps,
                // pre-gaps are simply run inline (rare edge case).
                $preFired = false;
                $capturedPreGaps = $preGaps;

                foreach ($visibleSteps as $step) {
                    $closure = $step['closure'];
                    $gap     = $step['gap'];

                    if ($closure === null) {
                        // Pending step — stays pending on run page too.
                        $builder->step($step['label'], pending: true);
                        continue;
                    }

                    // Wrap: fire pre-gaps once before first real closure,
                    // then fire the per-step gap after the step succeeds.
                    $wrapped = static function () use ($closure, $gap, $capturedPreGaps, &$preFired): bool|string {
                        // Pre-step gaps — fire exactly once
                        if (!$preFired) {
                            $preFired = true;
                            foreach ($capturedPreGaps as $preGap) {
                                try { $preGap(); } catch (\Throwable) { /* swallowed */ }
                            }
                        }
                        $result = $closure();
                        if ($result === true && $gap !== null) {
                            try { $gap(); } catch (\Throwable) { /* swallowed */ }
                        }
                        return $result;
                    };

                    $builder->step($step['label'], $wrapped);
                }

                $builder->submitStep('Review Setup Result', $completeUrl);
                $builder->render();
            },
        );

        // ── GET /__webkernel-app/setup/{token}/complete ───────────
        // Completion page — built-in, no manual registration needed.
        WebkernelRouter::register(
            'setup/{token}/complete',
            static function (array $params) use ($token, $signer, $tokenFile, $self): never {
                if (!hash_equals($token, $params['token'])) {
                    SetupFlow::renderBadToken();
                }

                // Determine success by re-running all fast-paths.
                // If any fast-path returns true, setup is complete.
                $ready = false;
                foreach ($self->fastPaths as $fp) {
                    if ($fp()) {
                        $ready = true;
                        break;
                    }
                }

                $canonicalUrl = WebkernelRouter::url('setup/{token}/complete', ['token' => $token]);

                if ($ready) {
                    // Burn the token — setup routes no longer valid.
                    @unlink($tokenFile);
                }

                $builder = EmergencyPageBuilder::create()
                    ->severity($ready ? 'SETUP' : 'WARNING')
                    ->code($ready ? 200 : 500)
                    ->systemState($ready ? 'SETUP COMPLETE' : 'SETUP INCOMPLETE')
                    ->canonicalize($canonicalUrl)
                    ->footer('WEBKERNEL — FIRST-RUN SETUP');

                if ($self->logoLight !== null || $self->logoDark !== null) {
                    $builder->logo($self->logoLight, $self->logoDark);
                }

                if ($ready) {
                    $builder
                        ->title($self->completeTitle)
                        ->message($self->completeMessage)
                        ->addButton($self->completeButtonLabel, $self->redirectAfterComplete);
                } else {
                    $retryUrl = WebkernelRouter::url('setup/{token}/run', ['token' => $token]);
                    $builder
                        ->title($self->incompleteTitle)
                        ->message($self->incompleteMessage)
                        ->addButton('Try Again', $retryUrl);
                }

                $builder->render();
            },
        );
    }

    // ── Token lifecycle ───────────────────────────────────────────

    /**
     * Load an existing valid token or generate a fresh one.
     *
     * Token file layout (HMAC-signed JSON):
     *   { "entropy": "<hex>", "token": "<url-safe-b64>", "created_at": <unix> }
     *
     * @return array{HmacSigner, string}
     */
    private function resolveToken(): array
    {
        $projectSalt = hash('sha256', $this->basePath);
        $ttl         = $this->tokenTtl;

        if (is_file($this->tokenFile)) {
            $raw = @file_get_contents($this->tokenFile);
            if ($raw !== false) {
                $dot = strrpos($raw, '.');
                if ($dot !== false) {
                    $jsonPart = substr($raw, 0, $dot);
                    $decoded  = json_decode($jsonPart, true);
                    if (
                        is_array($decoded)
                        && isset($decoded['entropy'], $decoded['token'], $decoded['created_at'])
                        && is_string($decoded['entropy'])
                        && is_string($decoded['token'])
                        && is_int($decoded['created_at'])
                    ) {
                        $candidate = new HmacSigner($projectSalt . ':' . $decoded['entropy']);
                        $verified  = $candidate->verifyArray($raw);
                        if ($verified !== null && (time() - $decoded['created_at']) < $ttl) {
                            return [$candidate, $decoded['token']];
                        }
                    }
                }
            }
        }

        // Generate fresh token
        $entropy  = bin2hex(
            function_exists('random_bytes') ?
            /** @disregard */
            random_bytes(32) : openssl_random_pseudo_bytes(32)
        );
        $signer   = new HmacSigner($projectSalt . ':' . $entropy);
        $newToken = $signer->token('setup', $this->basePath, (string) time());

        $signed = $signer->signArray([
            'entropy'    => $entropy,
            'token'      => $newToken,
            'created_at' => time(),
        ]);
        @file_put_contents($this->tokenFile, $signed, LOCK_EX);

        return [$signer, $newToken];
    }

    // ── Static helpers ────────────────────────────────────────────

    /**
     * Render a security block on token mismatch/expiry and terminate.
     * Public so route closures (which are static) can call it.
     */
    public static function renderBadToken(): never
    {
        EmergencyPageBuilder::create()
            ->title('Setup Link Expired or Invalid')
            ->message(
                "This setup link is no longer valid.\n\n"
                . "This can happen if:\n"
                . "  · The link has expired (tokens are valid for 24 hours)\n"
                . "  · The URL was modified or shared from another server\n"
                . "  · Setup has already been completed\n\n"
                . "Reload the application root to get a fresh setup link."
            )
            ->severity('WARNING')
            ->code(403)
            ->systemState('SETUP TOKEN INVALID')
            ->footer('WEBKERNEL — SETUP SECURITY CHECK FAILED')
            ->addButton('Return to Application Root', '/')
            ->render();
    }
}

// ═══════════════════════════════════════════════════════════════════
//  § 8  Global helpers
// ═══════════════════════════════════════════════════════════════════

/**
 * Return a fresh EmergencyPageBuilder (full-page mode).
 * Available globally — works inside Filament, Blade, raw PHP, anywhere.
 */
function webkernel_page(): EmergencyPageBuilder
{
    return EmergencyPageBuilder::create();
}

/**
 * Return a fresh EmergencyPageBuilder (modal mode).
 * Renders an HTML fragment — embed it anywhere in your views.
 */
function webkernel_modal(): EmergencyPageBuilder
{
    return EmergencyPageBuilder::modal();
}

/**
 * Shorthand — render a critical error page and terminate.
 */
function webkernel_abort(string $message, int $code = 500, string $severity = 'CRITICAL'): never
{
    EmergencyPageBuilder::create()
        ->message($message)
        ->code($code)
        ->severity($severity)
        ->render();
}
