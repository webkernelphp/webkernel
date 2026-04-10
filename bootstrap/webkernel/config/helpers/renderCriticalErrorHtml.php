<?php
declare(strict_types=1);

/**
 * ═══════════════════════════════════════════════════════════════════
 *  WebKernel — Error Response & HTTP Infrastructure
 * ═══════════════════════════════════════════════════════════════════
 *
 *  Provides three self-contained systems:
 *
 *  1. ErrorResponseBuilder  — Fluent, component-based full-page error/
 *                             block/security renderer with internal HTML
 *                             components, closure-based guard chains, and
 *                             server-side validation integration. Terminates
 *                             execution cleanly on every path (web + CLI).
 *
 *  2. ServerSideValidator   — Chainable, closure-friendly validator that
 *                             plugs directly into ErrorResponseBuilder.
 *                             Prevents any further action until all guards
 *                             pass. Supports submit-button gating so the
 *                             server only emits the "proceed" element when
 *                             the user is truly allowed to continue.
 *
 *  3. HttpClient            — Pure-PHP chainable HTTP client. cURL primary,
 *                             stream-context fallback. Fluent builder syntax
 *                             with an ->send() terminal (mirrors modern
 *                             async style without requiring any extension
 *                             beyond cURL). Supports JSON, form data,
 *                             bearer tokens, and custom headers.
 *
 *  Backward-compatible free functions are provided at the bottom so
 *  existing call-sites require zero changes.
 *
 * ═══════════════════════════════════════════════════════════════════
 */


// ═══════════════════════════════════════════════════════════════════
//  § 1 — ErrorResponseBuilder
// ═══════════════════════════════════════════════════════════════════

final class ErrorResponseBuilder
{
    // ── State ─────────────────────────────────────────────────────
    private string  $title              = 'System Error';
    private string  $message            = '';
    private int     $code               = 500;
    private string  $severity           = 'CRITICAL';
    private ?string $logBasePath        = null;
    private string  $systemState        = '';
    private string  $footerMessage      = '';
    private int     $autoRefreshSeconds = 0;
    private bool    $showRefreshButton  = false;

    /** @var list<array{text:string, href:string, style:string}> */
    private array $buttons = [];

    /** @var list<string> */
    private array $htmlComponents = [];

    /** @var list<\Closure():bool> */
    private array $guards = [];

    // ── Factory ───────────────────────────────────────────────────

    public static function create(): self
    {
        return new self();
    }

    // ── Core fluent setters ───────────────────────────────────────

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

    public function logTo(?string $basePath): self
    {
        $this->logBasePath = $basePath;
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

    /**
     * Enable auto-refresh. Pass 0 to disable (default).
     * The countdown widget is only rendered when $seconds > 0.
     */
    public function autoRefresh(int $seconds): self
    {
        $this->autoRefreshSeconds = max(0, $seconds);
        return $this;
    }

    public function withRefreshButton(bool $show = true): self
    {
        $this->showRefreshButton = $show;
        return $this;
    }

    // ── Component builders ────────────────────────────────────────

    /**
     * Add a styled action button to the error page.
     *
     * $href may be any valid href value:
     *   - 'location.reload()'   → triggers JS onclick
     *   - 'https://...'         → navigates away
     *   - 'mailto:...'          → mail client
     *   - '/logout'             → server route
     *
     * @param string $extraCss Additional inline CSS appended to the button.
     */
    public function addButton(
        string $text,
        string $href    = 'javascript:location.reload()',
        string $extraCss = '',
    ): self {
        $this->buttons[] = [
            'text'     => $text,
            'href'     => $href,
            'style'    => $extraCss,
        ];
        return $this;
    }

    /**
     * Append a raw HTML fragment inside the card content area.
     * Useful for custom info boxes, hidden inputs, inline forms, etc.
     * You are responsible for escaping any dynamic content inside $html.
     */
    public function addHtmlComponent(string $html): self
    {
        $this->htmlComponents[] = $html;
        return $this;
    }

    // ── Guard / closure chain ─────────────────────────────────────

    /**
     * Register a closure guard. All guards are evaluated lazily in order
     * when ->renderIfGuardFails() is called. If ANY guard returns false
     * the page renders and execution stops.
     *
     * Usage:
     *   ErrorResponseBuilder::create()
     *       ->accessBlocked('Account suspended')
     *       ->guard(fn() => $user->isActive())
     *       ->guard(fn() => !$user->isBanned())
     *       ->renderIfGuardFails();
     *
     * @param \Closure():bool $check
     */
    public function guard(\Closure $check): self
    {
        $this->guards[] = $check;
        return $this;
    }

    /**
     * Evaluate all registered guards. Renders and terminates only if at
     * least one guard returns false. Returns $this so you can keep chaining
     * after a no-op check.
     */
    public function renderIfGuardFails(): self
    {
        foreach ($this->guards as $guard) {
            if (!$guard()) {
                $this->render();
                // render() is `never` — the line below is unreachable but
                // satisfies static analysers.
                exit(1);
            }
        }
        return $this;
    }

    // ── Semantic presets ──────────────────────────────────────────

    /**
     * Preset: server-side validation failure.
     */
    public function validationFailed(string $fieldOrReason, int $code = 422): self
    {
        return $this
            ->title('Validation Failed')
            ->message($fieldOrReason)
            ->severity('WARNING')
            ->code($code);
    }

    /**
     * Preset: access blocked / user operation denied.
     */
    public function accessBlocked(string $reason, string $supportHref = ''): self
    {
        $builder = $this
            ->title('Access Blocked')
            ->message($reason)
            ->severity('WARNING')
            ->code(403);

        if ($supportHref !== '') {
            $builder->addButton('Contact Support', $supportHref);
        }

        return $builder;
    }

    /**
     * Preset: rate-limited / too many requests.
     */
    public function rateLimited(int $retryAfterSeconds = 60): self
    {
        return $this
            ->title('Too Many Requests')
            ->message("You have exceeded the allowed request rate. Please wait {$retryAfterSeconds} seconds before trying again.")
            ->severity('WARNING')
            ->code(429)
            ->autoRefresh($retryAfterSeconds)
            ->footer('RATE LIMIT — AUTOMATIC RETRY IN PROGRESS');
    }

    /**
     * Preset: maintenance / setup in progress.
     */
    public function maintenance(string $detail = 'The system is being updated. Please check back shortly.'): self
    {
        return $this
            ->title('Scheduled Maintenance')
            ->message($detail)
            ->severity('INFO')
            ->code(503);
    }

    // ── Server-side submit button gate ────────────────────────────

    /**
     * Emit a submit button HTML fragment only when all provided closures
     * return true. If any check fails, emits a disabled "not permitted"
     * element instead and DOES NOT render the full error page — the caller
     * handles flow. This is intentionally NOT a `never` path; it is used
     * inline while building a form response.
     *
     * Example (inside a form-rendering controller):
     *   echo ErrorResponseBuilder::gatedSubmitButton(
     *       label:   'Proceed to Payment',
     *       checks:  [
     *           fn() => $user->hasVerifiedEmail(),
     *           fn() => !$user->hasPendingDispute(),
     *       ],
     *       accent:  '#3b82f6',
     *   );
     *
     * @param list<\Closure():bool> $checks
     */
    public static function gatedSubmitButton(
        string $label,
        array  $checks,
        string $accent       = '#3b82f6',
        string $name         = 'submit',
        string $value        = '1',
        string $extraCss     = '',
    ): string {
        foreach ($checks as $check) {
            if (!$check()) {
                return sprintf(
                    '<button type="button" disabled
                        style="margin-top:1rem;padding:.6rem 1.4rem;background:transparent;'
                        . 'border:1px solid #333;color:#555;font-family:inherit;font-size:.75rem;'
                        . 'font-weight:600;text-transform:uppercase;letter-spacing:.08em;cursor:not-allowed;%s"'
                        . ' title="Action not permitted at this time">%s — NOT PERMITTED</button>',
                    htmlspecialchars($extraCss, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($label, ENT_QUOTES, 'UTF-8'),
                );
            }
        }

        return sprintf(
            '<button type="submit" name="%s" value="%s"
                style="margin-top:1rem;padding:.6rem 1.4rem;background:transparent;'
                . 'border:1px solid %s;color:%s;font-family:inherit;font-size:.75rem;'
                . 'font-weight:600;text-transform:uppercase;letter-spacing:.08em;cursor:pointer;%s">%s</button>',
            htmlspecialchars($name,     ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($value,    ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($accent,   ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($accent,   ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($extraCss, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($label,    ENT_QUOTES, 'UTF-8'),
        );
    }

    // ── Render ────────────────────────────────────────────────────

    /**
     * Render the full-page response and terminate.
     * This is the only `never` path in the builder.
     */
    public function render(): never
    {
        $incidentId = 'INC-' . strtoupper(
            substr(hash('sha256', $this->message . microtime(true)), 0, 7)
        );
        $timestamp = gmdate('Y-m-d\TH:i:s\Z');

        if ($this->logBasePath !== null) {
            self::writeIncidentLog(
                $incidentId,
                $this->severity,
                $this->title,
                $this->message,
                $this->code,
                $this->logBasePath,
            );
        }

        // ── CLI path ──────────────────────────────────────────────
        if (PHP_SAPI === 'cli') {
            $state = $this->systemState !== ''
                ? $this->systemState
                : 'SYSTEM STATE: ' . $this->severity;

            fwrite(STDERR, sprintf(
                "%s\nINCIDENT : %s\nSEVERITY : %s\nTIMESTAMP: %s\n\n%s\n%s\n",
                $state,
                $incidentId,
                $this->severity,
                $timestamp,
                strtoupper($this->title),
                $this->message,
            ));

            throw new \RuntimeException($this->message, $this->code);
        }

        // ── Derive palette ────────────────────────────────────────
        [$accent, $accentDim, $accentBorder, $defaultState, $defaultFooter, $iconSvg]
            = self::palette($this->severity);

        $resolvedState  = $this->systemState    !== '' ? $this->systemState    : $defaultState;
        $resolvedFooter = $this->footerMessage  !== '' ? $this->footerMessage  : $defaultFooter;

        // ── Escape ────────────────────────────────────────────────
        $eMessage  = htmlspecialchars($this->message,   ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $eSeverity = htmlspecialchars($this->severity,  ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $eTitle    = htmlspecialchars($this->title,     ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $eState    = htmlspecialchars($resolvedState,   ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $eFooter   = htmlspecialchars($resolvedFooter,  ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // ── Auto-refresh ──────────────────────────────────────────
        $metaRefresh   = '';
        $countdownHtml = '';
        $countdownJs   = '';

        if ($this->autoRefreshSeconds > 0) {
            $s = $this->autoRefreshSeconds;
            $metaRefresh   = "<meta http-equiv='refresh' content='{$s}'/>";
            $countdownHtml = "<div id='cd' class='cd'>Refreshing in <span id='cd-n'>{$s}</span>s\xe2\x80\xa6</div>";
            $countdownJs   = "<script>(function(){var s={$s},el=document.getElementById('cd-n');if(!el)return;var t=setInterval(function(){s--;el.textContent=s;if(s<=0)clearInterval(t);},1000);})();</script>";
        }

        // ── Buttons ───────────────────────────────────────────────
        $buttonsHtml = '';

        if ($this->showRefreshButton) {
            $buttonsHtml .= self::renderButton('Reload now', 'javascript:location.reload()', $accent, '');
        }

        foreach ($this->buttons as $btn) {
            $buttonsHtml .= self::renderButton($btn['text'], $btn['href'], $accent, $btn['style']);
        }

        // ── Extra HTML components ─────────────────────────────────
        $extraHtml = implode("\n", $this->htmlComponents);

        // ── Emit ──────────────────────────────────────────────────
        http_response_code($this->code);

        echo self::buildDocument(
            eState:        $eState,
            eTitle:        $eTitle,
            eSeverity:     $eSeverity,
            eMessage:      $eMessage,
            eFooter:       $eFooter,
            incidentId:    $incidentId,
            timestamp:     $timestamp,
            accent:        $accent,
            accentDim:     $accentDim,
            accentBorder:  $accentBorder,
            iconSvg:       $iconSvg,
            metaRefresh:   $metaRefresh,
            buttonsHtml:   $buttonsHtml,
            extraHtml:     $extraHtml,
            countdownHtml: $countdownHtml,
            countdownJs:   $countdownJs,
        );

        exit(1);
    }

    // ── Internal helpers ──────────────────────────────────────────

    /**
     * @return array{string, string, string, string, string, string}
     */
    private static function palette(string $sev): array
    {
        return match ($sev) {
            'INFO', 'SETUP' => [
                '#3b82f6',
                'rgba(59,130,246,.1)',
                '#3b82f6',
                'SETTING UP YOUR ENVIRONMENT',
                'PLEASE WAIT — THIS MAY TAKE A FEW SECONDS',
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
            default => [ // CRITICAL and anything unrecognised
                '#ff3333',
                'rgba(255,0,0,.08)',
                '#ff3333',
                'SYSTEM STATE: SEALED',
                'NO FURTHER ACTION IS PERMITTED',
                '<svg viewBox="0 0 24 24" fill="none" stroke="#ff3333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
            ],
        };
    }

    private static function renderButton(
        string $text,
        string $href,
        string $accent,
        string $extraCss,
    ): string {
        $eText  = htmlspecialchars($text,     ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $eHref  = htmlspecialchars($href,     ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $eExtra = htmlspecialchars($extraCss, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return sprintf(
            '<a href="%s" class="action-btn" style="border-color:%s;color:%s;%s">%s</a>',
            $eHref,
            $accent,
            $accent,
            $eExtra,
            $eText,
        );
    }

    /** Build the complete HTML document string. */
    private static function buildDocument(
        string $eState,
        string $eTitle,
        string $eSeverity,
        string $eMessage,
        string $eFooter,
        string $incidentId,
        string $timestamp,
        string $accent,
        string $accentDim,
        string $accentBorder,
        string $iconSvg,
        string $metaRefresh,
        string $buttonsHtml,
        string $extraHtml,
        string $countdownHtml,
        string $countdownJs,
    ): string {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <meta name="robots" content="noindex,nofollow"/>
  {$metaRefresh}
  <title>{$eState}</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

    /* Disable text selection and pointer events globally except buttons/links */
    ::selection, ::-moz-selection { background: transparent; }
    html, body {
      user-select: none;
      -webkit-user-select: none;
      -webkit-touch-callout: none;
      pointer-events: none;
    }
    button, a { pointer-events: all !important; }

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

    /* ── Card ───────────────────────────────────────────────── */
    .card {
      max-width: 680px;
      width: 100%;
      background: #0d0d0d;
      border: 1px solid #1a1a1a;
      overflow: hidden;
      box-shadow: 0 4px 32px rgba(0,0,0,.85);
    }

    /* ── Header ─────────────────────────────────────────────── */
    .card-header {
      background: #080808;
      border-bottom: 1px solid #1a1a1a;
      padding: .65rem 1rem;
      display: flex;
      align-items: center;
      gap: .75rem;
    }
    .header-state {
      flex: 1;
      font-size: .7rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .08em;
      color: #fff;
    }
    .header-incident {
      flex: 1;
      text-align: center;
      color: #666;
      font-size: .68rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .08em;
      font-family: 'Courier New', monospace;
    }
    .header-severity {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: .45rem;
    }
    .severity-icon {
      width: 15px;
      height: 15px;
      flex-shrink: 0;
    }
    .severity-icon svg { width: 100%; height: 100%; display: block; }
    .severity-label {
      color: {$accent};
      font-weight: 700;
      font-size: .7rem;
      text-transform: uppercase;
      letter-spacing: .08em;
    }

    /* ── Content ─────────────────────────────────────────────── */
    .card-body { padding: 1.25rem 1rem; }

    .identity {
      margin-bottom: 1rem;
      text-align: center;
    }
    .identity picture img {
      max-width: 220px;
      width: 100%;
      height: auto;
      display: block;
      margin: 0 auto .85rem;
      opacity: .85;
    }
    .incident-title {
      font-size: .82rem;
      font-weight: 600;
      color: {$accent};
      text-transform: uppercase;
      letter-spacing: .1em;
    }

    .msg-block {
      background: {$accentDim};
      border-left: 2px solid {$accentBorder};
      padding: .8rem .9rem;
      margin: .85rem 0;
      font-size: .8rem;
      line-height: 1.6;
      white-space: pre-wrap;
      word-break: break-word;
      color: #d0d0d0;
    }

    /* ── Action buttons ─────────────────────────────────────── */
    .actions {
      display: flex;
      flex-wrap: wrap;
      gap: .5rem;
      margin-top: .85rem;
    }
    .action-btn {
      display: inline-block;
      padding: .5rem 1.2rem;
      background: transparent;
      border: 1px solid {$accent};
      color: {$accent};
      font-family: inherit;
      font-size: .72rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .08em;
      text-decoration: none;
      cursor: pointer;
      transition: background .15s, color .15s;
    }
    .action-btn:hover {
      background: {$accentDim};
    }

    /* ── Countdown ───────────────────────────────────────────── */
    .cd {
      margin-top: .75rem;
      font-size: .68rem;
      color: rgba(255,255,255,.35);
      font-family: 'Courier New', monospace;
      letter-spacing: .05em;
    }

    /* ── Footer ──────────────────────────────────────────────── */
    .card-footer {
      padding: .65rem 1rem;
      text-align: center;
      font-size: .67rem;
      color: #555;
      text-transform: uppercase;
      letter-spacing: .08em;
      background: #080808;
      border-top: 1px solid #1a1a1a;
    }

    /* ── Timestamp ───────────────────────────────────────────── */
    .timestamp-bar {
      margin-top: 1.25rem;
      text-align: center;
      font-size: .63rem;
      color: rgba(255,255,255,.3);
      font-family: 'Courier New', monospace;
      letter-spacing: .05em;
    }

    /* ── Responsive ──────────────────────────────────────────── */
    @media (max-width: 480px) {
      .identity picture img { max-width: 160px; }
      .card-body { padding: .9rem .75rem; }
      .msg-block { font-size: .75rem; padding: .65rem .75rem; }
      .card-footer { font-size: .62rem; }
      .timestamp-bar { font-size: .58rem; margin-top: .9rem; }
      .header-incident { display: none; } /* declutter on tiny screens */
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
        <picture>
          <source srcset="/src-app/logo-dark-mode.png" media="(prefers-color-scheme:dark)"/>
          <img src="/src-app/logo-light-mode.png" alt="System" loading="eager"/>
        </picture>
        <div class="incident-title">{$eTitle}</div>
      </div>

      <div class="msg-block">{$eMessage}</div>

      {$extraHtml}

      <div class="actions">{$buttonsHtml}</div>

      {$countdownHtml}
    </div>

    <div class="card-footer">{$eFooter}</div>
  </div>

  <div class="timestamp-bar">TIMESTAMP (UTC): {$timestamp}</div>

  {$countdownJs}
</body>
</html>
HTML;
    }

    /** Write a structured entry to the incident log file. */
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
            $incidentId,
            $severity,
            $code,
            str_replace(["\r", "\n"], ' ', $title),
            str_replace(["\r", "\n"], ' ', $message),
            $_SERVER['HTTP_USER_AGENT'] ?? 'CLI',
            $_SERVER['REMOTE_ADDR']     ?? 'INTERNAL',
        );

        @file_put_contents("{$logDir}/critical-incidents.log", $entry, FILE_APPEND | LOCK_EX);
    }
}


// ═══════════════════════════════════════════════════════════════════
//  § 2 — ServerSideValidator
// ═══════════════════════════════════════════════════════════════════

/**
 * Chainable server-side validator.
 *
 * Validates an input array against a set of rules. Each rule may be:
 *   - A pipe-separated string rule:  'required|email|min:3|max:255'
 *   - A \Closure(mixed $value, array $data): bool|string
 *     returning true on pass, or an error message string on fail.
 *
 * Usage:
 *   $v = ServerSideValidator::check($_POST)
 *       ->field('email',    'required|email')
 *       ->field('username', 'required|min:3|max:32')
 *       ->field('age',      fn($v) => ((int)$v >= 18) ?: 'Must be 18 or older')
 *       ->onFail(fn(string $msg) =>
 *           ErrorResponseBuilder::create()->validationFailed($msg)->render()
 *       );
 *
 *   // Or let the validator render automatically:
 *   $v->renderOnFail();
 *
 *   // Check result programmatically:
 *   if ($v->passes()) { ... }
 */
final class ServerSideValidator
{
    /** @var array<string, mixed> */
    private array $data;

    /** @var list<array{field:string, rule:string|\Closure, label:string}> */
    private array $rules = [];

    /** @var list<string> */
    private array $errors = [];

    private bool $evaluated = false;

    private function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function check(array $data): self
    {
        return new self($data);
    }

    /**
     * @param string|\Closure(mixed $value, array $data): (bool|string) $rule
     */
    public function field(string $field, string|\Closure $rule, string $label = ''): self
    {
        $this->rules[] = [
            'field' => $field,
            'rule'  => $rule,
            'label' => $label !== '' ? $label : ucfirst(str_replace('_', ' ', $field)),
        ];
        return $this;
    }

    /** Run all rules. Returns $this for chaining. */
    public function evaluate(): self
    {
        if ($this->evaluated) {
            return $this;
        }

        $this->evaluated = true;

        foreach ($this->rules as $entry) {
            $field = $entry['field'];
            $label = $entry['label'];
            $value = $this->data[$field] ?? null;
            $rule  = $entry['rule'];

            if ($rule instanceof \Closure) {
                $result = $rule($value, $this->data);
                if ($result !== true) {
                    $this->errors[] = is_string($result) ? $result : "Field '{$label}' failed validation.";
                }
                continue;
            }

            // String rule pipeline
            foreach (explode('|', $rule) as $token) {
                $error = self::applyToken($token, $field, $label, $value);
                if ($error !== null) {
                    $this->errors[] = $error;
                    break; // one error per field
                }
            }
        }

        return $this;
    }

    public function passes(): bool
    {
        $this->evaluate();
        return $this->errors === [];
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    /** @return list<string> */
    public function errors(): array
    {
        $this->evaluate();
        return $this->errors;
    }

    public function firstError(): string
    {
        $this->evaluate();
        return $this->errors[0] ?? '';
    }

    /**
     * Call $callback with the first error message if validation fails.
     * The callback is responsible for any response/redirect/render.
     *
     * @param \Closure(string $firstError, list<string> $allErrors): void $callback
     */
    public function onFail(\Closure $callback): self
    {
        if ($this->fails()) {
            $callback($this->firstError(), $this->errors());
        }
        return $this;
    }

    /**
     * Automatically render a WARNING error page and terminate if validation fails.
     * The builder is yielded to an optional closure for customisation before render.
     *
     * @param \Closure(ErrorResponseBuilder $builder, string $firstError): void|null $customise
     */
    public function renderOnFail(?\Closure $customise = null): self
    {
        if ($this->fails()) {
            $builder = ErrorResponseBuilder::create()
                ->validationFailed($this->firstError());

            if ($customise !== null) {
                $customise($builder, $this->firstError());
            }

            $builder->render();
        }

        return $this;
    }

    // ── Internal rule engine ──────────────────────────────────────

    private static function applyToken(
        string $token,
        string $field,
        string $label,
        mixed  $value,
    ): ?string {
        // Normalise
        $str = is_string($value) ? trim($value) : '';

        [$name, $param] = str_contains($token, ':')
            ? explode(':', $token, 2)
            : [$token, ''];

        return match ($name) {
            'required' => ($value === null || $value === '' || $value === [])
                ? "'{$label}' is required."
                : null,

            'email' => (filter_var($value, FILTER_VALIDATE_EMAIL) === false)
                ? "'{$label}' must be a valid email address."
                : null,

            'url' => (filter_var($value, FILTER_VALIDATE_URL) === false)
                ? "'{$label}' must be a valid URL."
                : null,

            'numeric' => (!is_numeric($value))
                ? "'{$label}' must be numeric."
                : null,

            'integer' => (filter_var($value, FILTER_VALIDATE_INT) === false)
                ? "'{$label}' must be an integer."
                : null,

            'min' => (mb_strlen($str) < (int) $param)
                ? "'{$label}' must be at least {$param} characters."
                : null,

            'max' => (mb_strlen($str) > (int) $param)
                ? "'{$label}' must not exceed {$param} characters."
                : null,

            'min_value' => ((float) $value < (float) $param)
                ? "'{$label}' must be at least {$param}."
                : null,

            'max_value' => ((float) $value > (float) $param)
                ? "'{$label}' must not exceed {$param}."
                : null,

            'in' => (!in_array($str, explode(',', $param), true))
                ? "'{$label}' contains an unacceptable value."
                : null,

            'not_in' => (in_array($str, explode(',', $param), true))
                ? "'{$label}' contains a disallowed value."
                : null,

            'regex' => (!preg_match($param, $str))
                ? "'{$label}' format is invalid."
                : null,

            'confirmed' => true, // handled externally; no-op here
            default     => null,
        };
    }
}


// ═══════════════════════════════════════════════════════════════════
//  § 3 — HttpClient
// ═══════════════════════════════════════════════════════════════════

/**
 * Pure-PHP chainable HTTP client.
 *
 * Design goals:
 *   - Zero dependencies (cURL ext preferred; stream-context fallback)
 *   - Fluent builder with a single ->send() terminal
 *   - Supports JSON bodies, form data, bearer tokens, file uploads
 *   - Closure-based response interceptors for validation / logging
 *
 * Usage:
 *   $resp = HttpClient::request()
 *       ->post('https://api.example.com/verify')
 *       ->jsonBody(['token' => $token])
 *       ->bearerToken($key)
 *       ->timeout(10)
 *       ->expectStatus(200, fn() =>
 *           ErrorResponseBuilder::create()
 *               ->accessBlocked('Upstream verification failed.')
 *               ->render()
 *       )
 *       ->send();
 *
 *   $body = $resp->json();            // decoded array|null
 *   $ok   = $resp->successful();      // status 200-299
 */
final class HttpClient
{
    private string $method  = 'GET';
    private string $url     = '';
    private int    $timeout = 15;
    private bool   $verifySsl = true;

    /** @var array<string, string> */
    private array $headers = [];

    /** @var string|null Raw body */
    private ?string $rawBody = null;

    /** @var list<array{status:int, handler:\Closure():never}> */
    private array $statusHandlers = [];

    /** @var \Closure(HttpResponse): void|null */
    private ?\Closure $onSuccess = null;

    // ── Factory ───────────────────────────────────────────────────

    public static function request(): self
    {
        return new self();
    }

    // ── Method shortcuts ──────────────────────────────────────────

    public function get(string $url): self
    {
        $this->method = 'GET';
        $this->url    = $url;
        return $this;
    }

    public function post(string $url): self
    {
        $this->method = 'POST';
        $this->url    = $url;
        return $this;
    }

    public function put(string $url): self
    {
        $this->method = 'PUT';
        $this->url    = $url;
        return $this;
    }

    public function patch(string $url): self
    {
        $this->method = 'PATCH';
        $this->url    = $url;
        return $this;
    }

    public function delete(string $url): self
    {
        $this->method = 'DELETE';
        $this->url    = $url;
        return $this;
    }

    // ── Body builders ─────────────────────────────────────────────

    /**
     * Set a JSON request body. Sets Content-Type automatically.
     */
    public function jsonBody(array $data): self
    {
        $this->rawBody = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $this->headers['Content-Type'] = 'application/json';
        return $this;
    }

    /**
     * Set a URL-encoded form body. Sets Content-Type automatically.
     */
    public function formBody(array $data): self
    {
        $this->rawBody = http_build_query($data);
        $this->headers['Content-Type'] = 'application/x-www-form-urlencoded';
        return $this;
    }

    /**
     * Set raw string body with explicit content type.
     */
    public function body(string $raw, string $contentType = 'text/plain'): self
    {
        $this->rawBody = $raw;
        $this->headers['Content-Type'] = $contentType;
        return $this;
    }

    // ── Headers & auth ────────────────────────────────────────────

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function bearerToken(string $token): self
    {
        return $this->withHeader('Authorization', 'Bearer ' . $token);
    }

    public function basicAuth(string $username, string $password): self
    {
        return $this->withHeader(
            'Authorization',
            'Basic ' . base64_encode("{$username}:{$password}")
        );
    }

    public function accept(string $mimeType): self
    {
        return $this->withHeader('Accept', $mimeType);
    }

    // ── Options ───────────────────────────────────────────────────

    public function timeout(int $seconds): self
    {
        $this->timeout = max(1, $seconds);
        return $this;
    }

    /**
     * Disable SSL verification (only for local/test environments).
     */
    public function withoutSslVerification(): self
    {
        $this->verifySsl = false;
        return $this;
    }

    // ── Response interceptors ─────────────────────────────────────

    /**
     * Register a closure to call (and terminate) when a specific HTTP
     * status code is returned. Useful for inline error pages:
     *
     *   ->expectStatus(200, fn() =>
     *       ErrorResponseBuilder::create()
     *           ->accessBlocked('Upstream service denied access.')
     *           ->render()
     *   )
     *
     * @param \Closure():never $handler
     */
    public function expectStatus(int $status, \Closure $handler): self
    {
        // Store as "fail on anything other than this status"
        $this->statusHandlers[] = ['status' => $status, 'handler' => $handler];
        return $this;
    }

    /**
     * Register a closure called when the response is 2xx.
     *
     * @param \Closure(HttpResponse): void $callback
     */
    public function onSuccess(\Closure $callback): self
    {
        $this->onSuccess = $callback;
        return $this;
    }

    // ── Terminal ──────────────────────────────────────────────────

    /**
     * Execute the request and return an HttpResponse.
     * Named send() — mirrors modern HTTP client conventions.
     */
    public function send(): HttpResponse
    {
        $response = function_exists('curl_init')
            ? $this->sendViaCurl()
            : $this->sendViaStream();

        // Run status interceptors
        foreach ($this->statusHandlers as $entry) {
            if ($response->status() !== $entry['status']) {
                ($entry['handler'])();
                exit(1); // never reached if handler is `never`
            }
        }

        // onSuccess callback
        if ($this->onSuccess !== null && $response->successful()) {
            ($this->onSuccess)($response);
        }

        return $response;
    }

    // ── Transport implementations ─────────────────────────────────

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
            CURLOPT_USERAGENT      => 'WebKernel-HttpClient/2.0',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
        ]);

        if ($this->rawBody !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->rawBody);
        }

        $headerLines = [];
        foreach ($this->headers as $name => $value) {
            $headerLines[] = "{$name}: {$value}";
        }
        if ($headerLines !== []) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerLines);
        }

        $raw    = (string) curl_exec($ch);
        $status = (int)    curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $hSize  = (int)    curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $error  = curl_error($ch);
        curl_close($ch);

        $headers = substr($raw, 0, $hSize);
        $body    = substr($raw, $hSize);

        return new HttpResponse($status, $body, self::parseRawHeaders($headers), $error ?: null);
    }

    private function sendViaStream(): HttpResponse
    {
        $headerLines = [];
        foreach ($this->headers as $name => $value) {
            $headerLines[] = "{$name}: {$value}";
        }

        $opts = [
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
        ];

        $ctx  = stream_context_create($opts);
        $body = (string) @file_get_contents($this->url, false, $ctx);

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
        return array_filter(
            array_map('trim', explode("\r\n", $raw)),
            static fn(string $l) => $l !== '',
        );
    }
}

// ── HttpResponse value object ─────────────────────────────────────

final class HttpResponse
{
    public function __construct(
        private readonly int     $statusCode,
        private readonly string  $body,
        /** @var list<string> */
        private readonly array   $headers = [],
        private readonly ?string $error   = null,
    ) {}

    public function status(): int    { return $this->statusCode; }
    public function body(): string   { return $this->body; }
    public function error(): ?string { return $this->error; }

    public function successful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function failed(): bool { return !$this->successful(); }

    public function clientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    public function serverError(): bool
    {
        return $this->statusCode >= 500;
    }

    /** Decode the body as JSON. Returns null on failure. */
    public function json(): mixed
    {
        try {
            return json_decode($this->body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
    }

    /**
     * Return a specific JSON field from the decoded body.
     * Returns $default when the field is absent or body is not JSON.
     */
    public function jsonGet(string $key, mixed $default = null): mixed
    {
        $data = $this->json();
        return is_array($data) ? ($data[$key] ?? $default) : $default;
    }

    /**
     * Throw an ErrorResponseBuilder render if the response is not successful.
     *
     * @param \Closure(HttpResponse): void|null $customise
     */
    public function throwIfFailed(?\Closure $customise = null): self
    {
        if ($this->failed()) {
            $builder = ErrorResponseBuilder::create()
                ->title('Upstream Service Error')
                ->message("The remote service returned HTTP {$this->statusCode}.")
                ->severity('CRITICAL')
                ->code(502);

            if ($customise !== null) {
                $customise($this);
                // If customise renders, we never reach here
            }

            $builder->render();
        }

        return $this;
    }
}


// ═══════════════════════════════════════════════════════════════════
//  § 4 — Backward-compatible free functions
// ═══════════════════════════════════════════════════════════════════

if (!function_exists('renderCriticalErrorHtml')) {
    /**
     * Original function signature — fully preserved for zero-migration
     * compatibility. Delegates to ErrorResponseBuilder internally.
     */
    function renderCriticalErrorHtml(
        string  $title,
        string  $message,
        int     $code                = 500,
        string  $severity            = 'CRITICAL',
        ?string $exception           = null,
        ?string $logBasePath         = null,
        string  $systemState         = '',
        string  $footerMessage       = '',
        int     $autoRefreshSeconds  = 0,
        bool    $showRefreshButton   = false,
    ): never {
        ErrorResponseBuilder::create()
            ->title($title)
            ->message($message)
            ->code($code)
            ->severity($severity)
            ->logTo($logBasePath)
            ->systemState($systemState)
            ->footer($footerMessage)
            ->autoRefresh($autoRefreshSeconds)
            ->withRefreshButton($showRefreshButton)
            ->render();
    }
}

if (!function_exists('logCriticalIncident')) {
    /**
     * Original free function — kept for any existing call-sites that
     * invoke logging directly. Internally identical to the private
     * static method inside ErrorResponseBuilder.
     */
    function logCriticalIncident(
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
            $incidentId,
            $severity,
            $code,
            str_replace(["\r", "\n"], ' ', $title),
            str_replace(["\r", "\n"], ' ', $message),
            $_SERVER['HTTP_USER_AGENT'] ?? 'CLI',
            $_SERVER['REMOTE_ADDR']     ?? 'INTERNAL',
        );

        @file_put_contents("{$logDir}/critical-incidents.log", $entry, FILE_APPEND | LOCK_EX);
    }
}
