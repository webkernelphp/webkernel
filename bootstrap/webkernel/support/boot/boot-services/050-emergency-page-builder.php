<?php declare(strict_types=1);
// =============================================================================
//  § 4  MicroWebPage  (formerly EmergencyPageBuilder)
//  Declarative micro web-page renderer — full-page, modal, or debug overlay.
//
//  Modes
//  ─────
//    MicroWebPage::create()      → full-page HTML document
//    MicroWebPage::modal()       → inline modal fragment
//    MicroWebPage::debugModal()  → Ignition-style debug overlay
//
//  Traits
//  ─────────────────────────────────────────────────────────────────────────
//    HasMicroWebTheme    CSS tokens, colour palette, theme-switcher JS/HTML
//    HasMicroWebLogo     Logo registration, base64 inline, dual-image swap
//    HasMicroWebNotice   Notice bands (info | warning | error)
//    HasMicroWebButton   Action buttons with sm/md/lg/xl sizes and styles
//    HasMicroWebStep     Setup steps (ok / fail / pending) + submit gating
//    HasMicroWebModal    Self-contained modal fragment
//    HasMicroWebDebug    Ignition-style debug overlay
//    HasMicroWebPresets  Semantic shortcuts (validationFailed, maintenance…)
//
//  EmergencyPageBuilder is kept as a class alias for backwards compat.
// =============================================================================

// ── Load traits ───────────────────────────────────────────────────────────────

require_once __DIR__ . '/micro-web-page/HasMicroWebTheme.php';
require_once __DIR__ . '/micro-web-page/HasMicroWebLogo.php';
require_once __DIR__ . '/micro-web-page/HasMicroWebNotice.php';
require_once __DIR__ . '/micro-web-page/HasMicroWebButton.php';
require_once __DIR__ . '/micro-web-page/HasMicroWebStep.php';
require_once __DIR__ . '/micro-web-page/HasMicroWebModal.php';
require_once __DIR__ . '/micro-web-page/HasMicroWebDebug.php';
require_once __DIR__ . '/micro-web-page/HasMicroWebPresets.php';

// =============================================================================

final class MicroWebPage
{
    use HasMicroWebTheme;
    use HasMicroWebLogo;
    use HasMicroWebNotice;
    use HasMicroWebButton;
    use HasMicroWebStep;
    use HasMicroWebModal;
    use HasMicroWebDebug;
    use HasMicroWebPresets;

    // ── Mode ──────────────────────────────────────────────────────────────
    private bool $isModal      = false;
    private bool $isDebugModal = false;

    // ── Page identity ─────────────────────────────────────────────────────
    private string  $title         = 'System Error';
    private string  $message       = '';
    private int     $code          = 500;
    private string  $severity      = 'CRITICAL';
    private string  $systemState   = '';
    private string  $footerMessage = '';
    private ?string $logBasePath   = null;
    private ?string $canonicalUrl  = null;

    // ── HMAC ──────────────────────────────────────────────────────────────
    private ?HmacSigner $hmacSigner = null;

    // ── Extra HTML components ─────────────────────────────────────────────
    /** @var list<string> */
    private array $htmlComponents = [];

    // =========================================================================
    // Factory methods
    // =========================================================================

    public static function create(): self
    {
        return new self();
    }

    public static function modal(): self
    {
        $i          = new self();
        $i->isModal = true;
        return $i;
    }

    public static function debugModal(): self
    {
        $i               = new self();
        $i->isDebugModal = true;
        return $i;
    }

    // =========================================================================
    // Fluent identity setters
    // =========================================================================

    public function title(string $title): static          { $this->title         = $title;                      return $this; }
    public function message(string $message): static      { $this->message       = $message;                    return $this; }
    public function code(int $code): static               { $this->code          = $code;                       return $this; }
    public function severity(string $severity): static    { $this->severity      = strtoupper(trim($severity)); return $this; }
    public function systemState(string $state): static    { $this->systemState   = $state;                      return $this; }
    public function footer(string $footer): static        { $this->footerMessage = $footer;                     return $this; }
    public function logTo(?string $basePath): static      { $this->logBasePath   = $basePath;                   return $this; }
    public function canonicalize(string $url): static     { $this->canonicalUrl  = $url;                        return $this; }
    public function withSigner(HmacSigner $s): static     { $this->hmacSigner    = $s;                          return $this; }

    public function addHtmlComponent(string $html): static
    {
        $this->htmlComponents[] = $html;
        return $this;
    }

    // ── HMAC convenience ─────────────────────────────────────────────────

    public function hmac(string $data): string
    {
        if ($this->hmacSigner === null) {
            throw new \LogicException('Call withSigner() before hmac().');
        }
        return $this->hmacSigner->compute($data);
    }

    // ── HTTP client convenience ───────────────────────────────────────────

    public function sendHttpRequest(): HttpClient
    {
        return HttpClient::request();
    }

    // =========================================================================
    // renderToString
    // =========================================================================

    public function renderToString(): string
    {
        if ($this->isDebugModal) return $this->renderDebugModal();
        if ($this->isModal)      return $this->renderModal();

        $incidentId = 'INC-' . strtoupper(substr(hash('sha256', $this->message . microtime(true)), 0, 7));
        $timestamp  = gmdate('Y-m-d\TH:i:s\Z');

        if ($this->logBasePath !== null) {
            self::writeIncidentLog($incidentId, $this->severity, $this->title, $this->message, $this->code, $this->logBasePath);
        }

        [$accent, $accentDim, $accentBorder, $defaultState, $defaultFooter, $iconSvg] = self::palette($this->severity);

        $resolvedState  = $this->systemState   !== '' ? $this->systemState   : $defaultState;
        $resolvedFooter = $this->footerMessage !== '' ? $this->footerMessage : $defaultFooter;

        $noticesHtml  = $this->buildNoticesHtml();
        $logoHtml     = $this->buildLogoHtml();
        $stepsHtml    = $this->buildStepsHtml($accent);
        $buttonsHtml  = $this->buildButtonsHtml($accent);
        $extraHtml    = implode("\n", $this->htmlComponents);

        $canonicalTag = $this->canonicalUrl !== null
            ? '<link rel="canonical" href="' . htmlspecialchars($this->canonicalUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"/>'
            : '';

        $msgBlock = $this->message !== ''
            ? '<div class="wk-msg">' . $this->message . '</div>'
            : '';

        return self::buildDocument(
            eState:       htmlspecialchars($resolvedState,  ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            eTitle:       htmlspecialchars($this->title,    ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            eSeverity:    htmlspecialchars($this->severity, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            eFooter:      htmlspecialchars($resolvedFooter, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            incidentId:   $incidentId,
            timestamp:    $timestamp,
            accent:       $accent,
            accentDim:    $accentDim,
            accentBorder: $accentBorder,
            iconSvg:      $iconSvg,
            msgBlock:     $msgBlock,
            stepsHtml:    $stepsHtml,
            extraHtml:    $extraHtml,
            buttonsHtml:  $buttonsHtml,
            logoHtml:     $logoHtml,
            canonicalTag: $canonicalTag,
            noticesHtml:  $noticesHtml,
            logoLightSrc: $this->logoLight ?? '',
            logoDarkSrc:  $this->logoDark  ?? '',
        );
    }

    // =========================================================================
    // render — sends HTTP response and exits
    // =========================================================================

    public function render(): never
    {
        if (PHP_SAPI === 'cli') {
            $state = $this->systemState !== '' ? $this->systemState : 'SYSTEM STATE: ' . $this->severity;
            fwrite(STDERR, sprintf(
                "%s\nINCIDENT : %s\nSEVERITY : %s\n\n%s\n%s\n",
                $state,
                'INC-' . strtoupper(substr(hash('sha256', $this->message . microtime(true)), 0, 7)),
                $this->severity,
                strtoupper($this->title),
                $this->message
            ));
            throw new \RuntimeException($this->message, $this->code);
        }

        if ($this->canonicalUrl !== null) {
            header('X-Canonical-Url: ' . $this->canonicalUrl);
        }
        http_response_code($this->code);
        echo $this->renderToString();
        exit(1);
    }

    // =========================================================================
    // Document builder (private static)
    // =========================================================================

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
        string $noticesHtml  = '',
        string $logoLightSrc = '',
        string $logoDarkSrc  = '',
    ): string {
        $cssTokens    = self::cssTokens();
        $themeSwitcher = self::themeSwitcherHtml();
        $themeJs      = self::themeSwitcherJs($logoLightSrc, $logoDarkSrc);

        return <<<HTML
<!DOCTYPE html>
<html lang="en" data-wk-theme="">
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
    {$cssTokens}

    /* ── Reset ────────────────────────────────────────────────────────── */
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
    ::selection, ::-moz-selection { background:transparent; }
    html, body { user-select:none; -webkit-user-select:none; pointer-events:none; }
    a, button  { pointer-events:all !important; }

    /* ── Layout ───────────────────────────────────────────────────────── */
    body {
      font-family:'Space Grotesk',system-ui,sans-serif;
      background:var(--wk-bg);
      color:var(--wk-fg-dim);
      min-height:100dvh;
      display:flex; flex-direction:column;
      align-items:center; justify-content:center;
      padding:.75rem;
      transition:background .15s, color .15s;
    }

    /* ── Card ─────────────────────────────────────────────────────────── */
    .wk-card {
      max-width:680px; width:100%;
      background:var(--wk-card);
      border:1px solid var(--wk-border);
      overflow:hidden;
      box-shadow:var(--wk-shadow);
    }
    .wk-card-header {
      background:var(--wk-surface); border-bottom:1px solid var(--wk-border);
      padding:.65rem 1rem; display:flex; align-items:center; gap:.75rem;
    }
    .wk-header-state {
      flex:1; font-size:.68rem; font-weight:600;
      text-transform:uppercase; letter-spacing:.08em; color:var(--wk-fg);
    }
    .wk-header-incident {
      flex:1; text-align:center; color:var(--wk-muted);
      font-size:.65rem; font-weight:600;
      text-transform:uppercase; letter-spacing:.08em;
      font-family:'Courier New',monospace;
    }
    .wk-header-severity {
      flex:1; display:flex; align-items:center; justify-content:flex-end; gap:.4rem;
    }
    .wk-severity-icon { width:15px; height:15px; flex-shrink:0; }
    .wk-severity-icon svg { width:100%; height:100%; display:block; }
    .wk-severity-label {
      color:{$accent}; font-weight:700; font-size:.68rem;
      text-transform:uppercase; letter-spacing:.08em;
    }

    /* ── Card body ────────────────────────────────────────────────────── */
    .wk-card-body { padding:1.25rem 1rem; }
    .wk-identity  { margin-bottom:1rem; text-align:center; }
    .wk-page-title {
      font-size:.8rem; font-weight:600; color:{$accent};
      text-transform:uppercase; letter-spacing:.1em;
    }

    /* ── Notice bands ─────────────────────────────────────────────────── */
    .wk-notice {
      padding:.6rem .85rem; margin-bottom:.75rem;
      font-size:.74rem; line-height:1.55; color:var(--wk-fg-dim);
    }

    /* ── Message block ────────────────────────────────────────────────── */
    .wk-msg {
      background:{$accentDim}; border-left:2px solid {$accentBorder};
      padding:.8rem .9rem; margin:.85rem 0;
      font-size:.8rem; line-height:1.6;
      white-space:pre-wrap; word-break:break-word; color:var(--wk-fg-dim);
    }

    /* ── Steps ────────────────────────────────────────────────────────── */
    .wk-steps   { margin:.85rem 0; display:flex; flex-direction:column; gap:.3rem; }
    .wk-step    { display:flex; align-items:flex-start; gap:.55rem; font-size:.78rem; padding:.4rem .45rem; }
    .wk-step-ok      { background:{$accentDim}; }
    .wk-step-fail    { background:rgba(239,68,68,.05); }
    .wk-step-pending { background:transparent; }
    .wk-step-icon    { font-family:monospace; font-size:.82rem; line-height:1.35; flex-shrink:0; width:1rem; text-align:center; }
    .wk-step-label   { color:var(--wk-fg-dim); line-height:1.4; }
    .wk-step-detail  { display:block; margin-top:.2rem; font-size:.69rem; color:#ef4444; font-family:'Courier New',monospace; }

    /* ── Submit row ───────────────────────────────────────────────────── */
    .wk-submit-row     { margin-top:1rem; padding-top:.85rem; border-top:1px solid var(--wk-border); }
    .wk-submit-blocked { font-size:.7rem; color:var(--wk-muted); text-transform:uppercase; letter-spacing:.06em; }
    .wk-proceed-btn {
      display:inline-block; padding:.55rem 1.4rem; background:transparent;
      border:1px solid {$accent}; color:{$accent}; font-family:inherit;
      font-size:.73rem; font-weight:600; text-transform:uppercase;
      letter-spacing:.08em; text-decoration:none; transition:background .14s;
    }
    .wk-proceed-btn:hover { background:{$accentDim}; }

    /* ── Action buttons ───────────────────────────────────────────────── */
    .wk-actions { display:flex; flex-wrap:wrap; gap:.5rem; margin-top:.85rem; }
    .wk-btn {
      display:inline-block; background:transparent; border:1px solid;
      font-family:inherit; font-weight:600;
      text-transform:uppercase; letter-spacing:.08em;
      text-decoration:none; transition:background .14s;
      /* size overridden inline from HasMicroWebButton */
    }
    .wk-btn:hover { opacity:.8; }

    /* ── Card footer ──────────────────────────────────────────────────── */
    .wk-card-footer {
      padding:.55rem 1rem;
      background:var(--wk-surface); border-top:1px solid var(--wk-border);
      display:flex; align-items:center; justify-content:space-between;
      gap:.5rem; flex-wrap:wrap;
    }
    .wk-footer-left  { font-size:.62rem; color:var(--wk-muted); text-transform:uppercase; letter-spacing:.08em; }
    .wk-footer-right { display:flex; align-items:center; gap:.45rem; }

    /* ── Theme switcher ───────────────────────────────────────────────── */
    .wk-theme-switcher {
      display:inline-flex; align-items:center; gap:2px;
      background:var(--wk-border); padding:2px;
    }
    .wk-theme-btn {
      background:none; border:none; cursor:pointer;
      padding:.28rem .38rem; color:var(--wk-muted);
      display:inline-flex; align-items:center; justify-content:center;
      transition:background .12s, color .12s; font-family:inherit;
    }
    .wk-theme-btn:hover  { color:var(--wk-fg); }
    .wk-theme-btn.wk-active { background:var(--wk-card); color:var(--wk-fg); }

    /* ── Debug trigger button ────────────────────────────────────────── */
    .wk-debug-trigger {
      display:none; align-items:center;
      padding:.26rem .6rem; background:transparent;
      border:1px solid {$accent}; color:{$accent};
      font-size:.6rem; text-transform:uppercase; letter-spacing:.06em;
      font-family:inherit; cursor:pointer; transition:background .12s;
    }
    .wk-debug-trigger.wk-visible { display:inline-flex; }
    .wk-debug-trigger:hover      { background:{$accentDim}; }

    /* ── Timestamp bar ────────────────────────────────────────────────── */
    .wk-timestamp {
      margin-top:1.1rem; text-align:center; font-size:.6rem;
      color:var(--wk-muted); font-family:'Courier New',monospace; letter-spacing:.05em;
    }

    /* ── Responsive ───────────────────────────────────────────────────── */
    @media (max-width:480px) {
      .wk-card-body          { padding:.9rem .75rem; }
      .wk-msg                { font-size:.74rem; padding:.65rem .75rem; }
      .wk-footer-left        { font-size:.58rem; }
      .wk-timestamp          { font-size:.55rem; margin-top:.75rem; }
      .wk-header-incident    { display:none; }
      .wk-step               { font-size:.74rem; }
    }
  </style>
</head>
<body>
  <div class="wk-card">
    <div class="wk-card-header">
      <div class="wk-header-state">{$eState}</div>
      <div class="wk-header-incident">{$incidentId}</div>
      <div class="wk-header-severity">
        <span class="wk-severity-icon">{$iconSvg}</span>
        <span class="wk-severity-label">{$eSeverity}</span>
      </div>
    </div>

    <div class="wk-card-body">
      <div class="wk-identity">
        {$logoHtml}
        <div class="wk-page-title">{$eTitle}</div>
      </div>

      {$noticesHtml}
      {$msgBlock}
      {$stepsHtml}
      {$extraHtml}
      {$buttonsHtml}
    </div>

    <div class="wk-card-footer">
      <span class="wk-footer-left">{$eFooter}</span>
      <div class="wk-footer-right">
        <button id="wk-debug-open-btn"
                class="wk-debug-trigger"
                onclick="wkdOpen()">Debug</button>
        {$themeSwitcher}
      </div>
    </div>
  </div>

  <div class="wk-timestamp">TIMESTAMP (UTC): {$timestamp}</div>

  <script>{$themeJs}</script>
</body>
</html>
HTML;
    }

    // =========================================================================
    // Log writer
    // =========================================================================

    private static function writeIncidentLog(
        string $incidentId, string $severity, string $title,
        string $message, int $code, string $basePath
    ): void {
        $logDir = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);

        $entry = sprintf(
            "[%s] INCIDENT:%s | SEV:%s | CODE:%d | TITLE:%s | MSG:%s | UA:%s | IP:%s\n",
            gmdate('Y-m-d\TH:i:s\Z'),
            $incidentId, $severity, $code,
            str_replace(["\r", "\n"], ' ', $title),
            str_replace(["\r", "\n"], ' ', $message),
            $_SERVER['HTTP_USER_AGENT'] ?? 'CLI',
            $_SERVER['REMOTE_ADDR']     ?? 'INTERNAL'
        );
        @file_put_contents($logDir . '/critical-incidents.log', $entry, FILE_APPEND | LOCK_EX);
    }
}
