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
 *  │  § 7  Global helpers      webkernel_page(), webkernel_modal()│
 *  └─────────────────────────────────────────────────────────────┘
 *
 * ═══════════════════════════════════════════════════════════════════
 *
 *  Route scheme
 *  ────────────────────────────────────────────────────────────────
 *  All Webkernel UI lives under:
 *
 *    /__webkernel-app/{flow}/{token}
 *
 *  e.g.  /__webkernel-app/setup/a3f9b1c2d4e5f6a7
 *        /__webkernel-app/setup/a3f9b1c2d4e5f6a7/confirm
 *        /__webkernel-app/error/b2e7d9f1
 *
 *  Tokens are HMAC-SHA256 derived, opaque, unguessable.
 *  No query strings. No leaking intent in the URL.
 *
 * ═══════════════════════════════════════════════════════════════════
 *
 *  EmergencyPageBuilder — quick reference
 *  ────────────────────────────────────────────────────────────────
 *
 *  A) Simple error / security block:
 *
 *     EmergencyPageBuilder::create()
 *         ->title('Access Denied')
 *         ->message('Your account is suspended.')
 *         ->severity('WARNING')
 *         ->code(403)
 *         ->addButton('Contact Support', '/__webkernel-app/support/token')
 *         ->render();
 *
 *  B) Guard chain:
 *
 *     EmergencyPageBuilder::create()
 *         ->accessBlocked('Operation not permitted.')
 *         ->guard(fn() => $user->isActive())
 *         ->renderIfGuardFails();
 *
 *  C) Multi-step setup page:
 *
 *     EmergencyPageBuilder::create()
 *         ->title('Initial Setup')
 *         ->severity('SETUP')
 *         ->step('Reading template',   fn() => loadTemplate())
 *         ->step('Generating key',     fn() => generateKey())
 *         ->step('Writing .env',       fn() => writeEnv())
 *         ->step('Migrations', pending: true)
 *         ->submitStep('Open Application', '/')
 *         ->render();
 *
 *  D) macOS-style modal (renders inline, caller handles display):
 *
 *     echo EmergencyPageBuilder::modal()
 *         ->title('Confirm Action')
 *         ->message('This cannot be undone.')
 *         ->modalButton('Cancel',  '#', 'cancel')
 *         ->modalButton('Proceed', '/confirm-url', 'destructive')
 *         ->renderModal();
 *
 *  E) Available anywhere in Filament / Laravel views:
 *
 *     webkernel_page()->accessBlocked('Nope.')->render();
 *     echo webkernel_modal()->title('Sure?')->renderModal();
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
     * The token is URL-safe base64, 32 chars by default.
     */
    public function token(string ...$parts): string
    {
        $payload = implode('|', $parts) . '|' . microtime(true) . '|' . random_int(0, PHP_INT_MAX);
        $raw     = hash_hmac($this->algo, $payload, $this->secret, true);
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    /**
     * Sign a payload string. Returns "payload.signature".
     */
    public function sign(string $payload): string
    {
        $sig = hash_hmac($this->algo, $payload, $this->secret);
        return $payload . '.' . $sig;
    }

    /**
     * Verify and extract payload from a signed string.
     * Returns null on tamper.
     */
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

    /**
     * Sign an arbitrary array as JSON. Returns opaque signed blob.
     */
    public function signArray(array $data): string
    {
        return $this->sign(json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Verify and decode a signed JSON array. Returns null on tamper/invalid.
     */
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

    /**
     * Compute a bare HMAC hex string for arbitrary data.
     */
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
 *
 * State is stored as a HMAC-signed JSON file in the system temp
 * directory. No cookies, no PHP session. The session ID is the
 * token in the URL — the user cannot forge it without the secret.
 *
 * Usage:
 *   $sess = WebkernelSession::load($token, $signer);
 *   $sess->set('step', 2);
 *   $sess->save();
 *   $sess->destroy();
 */
final class WebkernelSession
{
    private array  $data    = [];
    private bool   $dirty   = false;

    private function __construct(
        private readonly string      $token,
        private readonly HmacSigner  $signer,
        private readonly string      $storePath,
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
                // Expire after 2 hours of inactivity
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
 * Call WebkernelRouter::dispatch() at pre-boot entry points.
 * If the current request matches a registered Webkernel route,
 * the handler is invoked and execution terminates.
 * If no route matches, dispatch() returns normally so the
 * framework (Laravel, etc.) can take over.
 *
 * Route patterns support named segments: {token}, {flow}, etc.
 * The prefix /__webkernel-app is always implicit.
 *
 * Usage:
 *   WebkernelRouter::register('setup/{token}',         $handler);
 *   WebkernelRouter::register('setup/{token}/confirm', $confirmHandler);
 *   WebkernelRouter::dispatch();
 *
 * Inside a handler:
 *   function(array $params): never {
 *       $token = $params['token'];
 *       ...
 *       exit;
 *   }
 */
final class WebkernelRouter
{
    private const PREFIX = '/__webkernel-app/';

    /** @var list<array{pattern: string, handler: \Closure}> */
    private static array $routes = [];

    /**
     * Register a route under /__webkernel-app/.
     *
     * @param \Closure(array<string,string> $params): void $handler
     */
    public static function register(string $pattern, \Closure $handler): void
    {
        self::$routes[] = ['pattern' => $pattern, 'handler' => $handler];
    }

    /**
     * Attempt to dispatch the current request.
     * Returns true if a route matched (and the handler ran).
     * Returns false if no route matched — caller may continue.
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
                // If handler returns (non-never), we still stop dispatching
                return true;
            }
        }

        return false;
    }

    /**
     * Generate a canonical /__webkernel-app/ URL.
     */
    public static function url(string $pattern, array $params = []): string
    {
        $path = $pattern;
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', rawurlencode((string) $value), $path);
        }
        return self::PREFIX . ltrim($path, '/');
    }

    /**
     * Match a route pattern against a URI segment.
     * Returns an array of named parameters, or null on no match.
     *
     * @return array<string,string>|null
     */
    private static function match(string $pattern, string $uri): ?array
    {
        // Convert {name} placeholders to named capture groups
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', static function (array $m): string {
            return '(?P<' . $m[1] . '>[^/]+)';
        }, $pattern);

        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $uri, $matches)) {
            return null;
        }

        // Extract only named captures
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
    /**
     * @var list<array{text:string, href:string, style:'default'|'cancel'|'destructive'}>
     */
    private array $modalButtons = [];

    // ── Guards ────────────────────────────────────────────────────
    /** @var list<\Closure():bool> */
    private array $guards = [];

    // ── Steps ─────────────────────────────────────────────────────
    /**
     * @var list<array{label:string, closure:\Closure|null, pending:bool}>
     */
    private array $steps = [];

    /**
     * @var array{label:string, href:string, extraCss:string}|null
     */
    private ?array $submitStep = null;

    // ── Embedded logos (base64 PNG/SVG, optional) ─────────────────
    private ?string $logoLight = null;   // base64 data-URI, light mode
    private ?string $logoDark  = null;   // base64 data-URI, dark mode

    // ── Factory ───────────────────────────────────────────────────

    public static function create(): self
    {
        return new self();
    }

    /**
     * Start a modal builder (renders HTML fragment, not a full page).
     */
    public static function modal(): self
    {
        $instance          = new self();
        $instance->isModal = true;
        return $instance;
    }

    // ── Fluent setters ────────────────────────────────────────────

    public function title(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function message(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function code(int $code): self
    {
        $this->code = $code;
        return $this;
    }

    public function severity(string $severity): self
    {
        $this->severity = strtoupper(trim($severity));
        return $this;
    }

    public function systemState(string $state): self
    {
        $this->systemState = $state;
        return $this;
    }

    public function footer(string $footer): self
    {
        $this->footerMessage = $footer;
        return $this;
    }

    public function logTo(?string $basePath): self
    {
        $this->logBasePath = $basePath;
        return $this;
    }

    /**
     * Declare the canonical URL for this page.
     * Emits a <link rel="canonical"> and X-Canonical-Url header.
     * Use WebkernelRouter::url() to generate the value.
     */
    public function canonicalize(string $url): self
    {
        $this->canonicalUrl = $url;
        return $this;
    }

    /**
     * Attach an HmacSigner for use with hmac() helper or signed forms.
     */
    public function withSigner(HmacSigner $signer): self
    {
        $this->hmacSigner = $signer;
        return $this;
    }

    /**
     * Compute an HMAC for arbitrary data using the attached signer.
     * Throws if no signer was attached.
     */
    public function hmac(string $data): string
    {
        if ($this->hmacSigner === null) {
            throw new \LogicException('Call withSigner() before hmac().');
        }
        return $this->hmacSigner->compute($data);
    }

    /**
     * Execute an HTTP request from the builder context.
     * Returns the HttpResponse — check ->successful() before using.
     */
    public function sendHttpRequest(): HttpClient
    {
        return HttpClient::request();
    }

    /**
     * Embed logo images (base64 data-URIs).
     *
     * Pass null to either argument to skip that variant.
     * If only one is provided it is used for both modes.
     *
     * Example:
     *   ->logo(
     *       light: 'data:image/png;base64,' . base64_encode(file_get_contents('logo-light.png')),
     *       dark:  'data:image/png;base64,' . base64_encode(file_get_contents('logo-dark.png')),
     *   )
     *
     * You may also pass a plain file path — the builder will encode it:
     *   ->logo(light: '/var/www/public/logo.png')
     */
    public function logo(?string $light = null, ?string $dark = null): self
    {
        $this->logoLight = self::resolveLogoSrc($light ?? $dark);
        $this->logoDark  = self::resolveLogoSrc($dark  ?? $light);
        return $this;
    }

    private static function resolveLogoSrc(?string $src): ?string
    {
        if ($src === null) {
            return null;
        }
        // Already a data-URI
        if (str_starts_with($src, 'data:')) {
            return $src;
        }
        // File path — encode it
        if (is_file($src)) {
            $mime = match (strtolower(pathinfo($src, PATHINFO_EXTENSION))) {
                'svg'  => 'image/svg+xml',
                'webp' => 'image/webp',
                'jpg', 'jpeg' => 'image/jpeg',
                default       => 'image/png',
            };
            $raw = @file_get_contents($src);
            if ($raw !== false) {
                return 'data:' . $mime . ';base64,' . base64_encode($raw);
            }
        }
        // Treat as-is (URL, relative path)
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

    /**
     * Add a button to a modal dialog.
     *
     * $style:
     *   'default'     → primary action (accent border)
     *   'cancel'      → neutral dismiss (gray border)
     *   'destructive' → danger action (red border)
     */
    public function modalButton(
        string $text,
        string $href,
        string $style = 'default',
    ): self {
        $this->modalButtons[] = ['text' => $text, 'href' => $href, 'style' => $style];
        return $this;
    }

    // ── Steps ─────────────────────────────────────────────────────

    /**
     * @param (\Closure():bool)|(\Closure():string)|null $closure
     */
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

    /**
     * @param list<\Closure():bool> $checks
     */
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

    /**
     * Render a macOS-style alert modal as an HTML fragment.
     *
     * The fragment is a self-contained <div> with an overlay backdrop.
     * Drop it anywhere in a page — Filament, Blade, raw HTML.
     * It uses fixed positioning with a high z-index.
     *
     * The modal has no JS dependency. Buttons are plain <a> links.
     * Wrap in an Alpine x-show or Livewire conditional for toggling.
     */
    public function renderModal(): string
    {
        [$accent, $accentDim, , , , $iconSvg] = self::palette($this->severity);

        $eTitle   = htmlspecialchars($this->title,   ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $msgBlock = $this->message !== '' ? htmlspecialchars($this->message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '';

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
                $borderColor,
                $textColor,
                htmlspecialchars($btn['text'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            );
        }

        // If no modal buttons were registered, add a sensible default dismiss
        if ($buttons === '') {
            $buttons = sprintf(
                '<a href="/" style="display:inline-flex;align-items:center;justify-content:center;'
                . 'min-width:80px;padding:.45rem 1.1rem;background:transparent;border:1px solid #444;'
                . 'color:#888;font-family:inherit;font-size:.72rem;font-weight:600;'
                . 'text-transform:uppercase;letter-spacing:.07em;text-decoration:none;'
                . 'border-radius:4px;">OK</a>',
            );
        }

        return <<<HTML
<div class="wk-modal-overlay" style="
    position:fixed;inset:0;z-index:9999;
    display:flex;align-items:center;justify-content:center;
    background:rgba(0,0,0,.6);
    backdrop-filter:blur(4px);
    -webkit-backdrop-filter:blur(4px);
    padding:1rem;
    font-family:'Space Grotesk',system-ui,sans-serif;
">
  <div class="wk-modal" role="alertdialog" aria-modal="true" aria-labelledby="wk-modal-title" style="
      background:#111;
      border:1px solid #222;
      border-radius:12px;
      width:100%;max-width:380px;
      padding:1.5rem 1.5rem 1.25rem;
      box-shadow:0 20px 60px rgba(0,0,0,.8);
      text-align:center;
  ">
    <div style="width:36px;height:36px;margin:0 auto .85rem;opacity:.9">{$iconSvg}</div>
    <div id="wk-modal-title" style="font-size:.9rem;font-weight:700;color:#fff;letter-spacing:.02em;margin-bottom:.5rem;">{$eTitle}</div>
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
            // Modal misuse — just echo the fragment and exit
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

        // ── Canonical header ──────────────────────────────────────
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

        // ── Escape structural strings ─────────────────────────────
        $eSeverity = htmlspecialchars($this->severity,  ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $eTitle    = htmlspecialchars($this->title,     ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $eState    = htmlspecialchars($resolvedState,   ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $eFooter   = htmlspecialchars($resolvedFooter,  ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // ── Buttons ───────────────────────────────────────────────
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
        $msgBlock  = $this->message !== ''
            ? "<div class=\"msg-block\">{$this->message}</div>"
            : '';

        // ── Logo HTML ─────────────────────────────────────────────
        $logoHtml = $this->buildLogoHtml();

        // ── Canonical link tag ────────────────────────────────────
        $canonicalTag = $this->canonicalUrl !== null
            ? '<link rel="canonical" href="' . htmlspecialchars($this->canonicalUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"/>'
            : '';

        // ── Emit ──────────────────────────────────────────────────
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
        // Neither provided — use default path-based picture element
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

        // Embedded base64 data-URIs — no HTTP request needed
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
    private static function palette(string $sev): array
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
    private bool  $evaluated = false;

    private function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function check(array $data): self
    {
        return new self($data);
    }

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
        if ($this->evaluated) {
            return $this;
        }
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

    public function passes(): bool       { return $this->evaluate()->errors === []; }
    public function fails(): bool        { return !$this->passes(); }
    /** @return list<string> */
    public function errors(): array      { return $this->evaluate()->errors; }
    public function firstError(): string { return $this->evaluate()->errors[0] ?? ''; }

    /** @param \Closure(string, list<string>): void $callback */
    public function onFail(\Closure $callback): self
    {
        if ($this->fails()) {
            $callback($this->firstError(), $this->errors());
        }
        return $this;
    }

    /** @param \Closure(EmergencyPageBuilder, string): void|null $customise */
    public function renderOnFail(?\Closure $customise = null): self
    {
        if ($this->fails()) {
            $builder = EmergencyPageBuilder::create()->validationFailed($this->firstError());
            if ($customise !== null) {
                $customise($builder, $this->firstError());
            }
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
        $this->rawBody                    = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $this->headers['Content-Type']    = 'application/json';
        return $this;
    }

    public function formBody(array $data): self
    {
        $this->rawBody                    = http_build_query($data);
        $this->headers['Content-Type']    = 'application/x-www-form-urlencoded';
        return $this;
    }

    public function body(string $raw, string $contentType = 'text/plain'): self
    {
        $this->rawBody                    = $raw;
        $this->headers['Content-Type']    = $contentType;
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

    public function status(): int    { return $this->statusCode; }
    public function body(): string   { return $this->body; }
    public function error(): ?string { return $this->error; }
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
//  § 7  Global helpers
// ═══════════════════════════════════════════════════════════════════

/**
 * Return a fresh EmergencyPageBuilder (full-page mode).
 * Available globally — works inside Filament, Blade, raw PHP, anywhere.
 *
 * Examples:
 *   webkernel_page()->accessBlocked('Nope.')->render();
 *   webkernel_page()->title('Oops')->message('Something broke.')->render();
 */
function webkernel_page(): EmergencyPageBuilder
{
    return EmergencyPageBuilder::create();
}

/**
 * Return a fresh EmergencyPageBuilder (modal mode).
 * Renders an HTML fragment — embed it anywhere in your views.
 *
 * Examples:
 *   echo webkernel_modal()
 *       ->title('Confirm deletion')
 *       ->message('This cannot be undone.')
 *       ->modalButton('Cancel',  '#',       'cancel')
 *       ->modalButton('Delete',  '/delete', 'destructive')
 *       ->renderModal();
 */
function webkernel_modal(): EmergencyPageBuilder
{
    return EmergencyPageBuilder::modal();
}

/**
 * Shorthand — render a critical error page and terminate.
 * Use webkernel_page() for full fluent control.
 */
function webkernel_abort(string $message, int $code = 500, string $severity = 'CRITICAL'): never
{
    EmergencyPageBuilder::create()
        ->message($message)
        ->code($code)
        ->severity($severity)
        ->render();
}
