<?php declare(strict_types=1);

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
