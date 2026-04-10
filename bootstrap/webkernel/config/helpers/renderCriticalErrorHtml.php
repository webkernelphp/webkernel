<?php
declare(strict_types=1);

/**
 * ═══════════════════════════════════════════════════════════════════
 *  WebKernel — Emergency Page, Validation & HTTP Infrastructure
 * ═══════════════════════════════════════════════════════════════════
 *
 *  Three self-contained systems in one file:
 *
 *  1. EmergencyPageBuilder   Fluent builder for every full-page
 *                            termination scenario: critical errors,
 *                            security blocks, validation failures,
 *                            and multi-step progress pages.
 *                            Steps with closures are first-class
 *                            citizens. submitStep() emits the real
 *                            "proceed" element — a plain <a> link,
 *                            no JS — only when all steps passed.
 *
 *  2. ServerSideValidator    Chainable rule engine with closure
 *                            support. Plugs directly into
 *                            EmergencyPageBuilder. Drives submit-
 *                            gating so the server never sends a
 *                            working action to an ineligible user.
 *
 *  3. HttpClient             Pure-PHP chainable HTTP client.
 *                            cURL primary, stream-context fallback.
 *                            ->send() terminal, closure interceptors.
 *
 *  Backward-compatible free functions at the bottom keep every
 *  existing call-site working without modification.
 *
 * ═══════════════════════════════════════════════════════════════════
 *
 *  Quick-reference: EmergencyPageBuilder patterns
 *  ────────────────────────────────────────────────────────────────
 *
 *  A) Simple error / security block:
 *
 *     EmergencyPageBuilder::create()
 *         ->title('Access Denied')
 *         ->message('Your account is suspended pending review.')
 *         ->severity('WARNING')
 *         ->code(403)
 *         ->addButton('Contact Support', 'mailto:support@example.com')
 *         ->render();
 *
 *  B) Guard chain — only renders when a check fails:
 *
 *     EmergencyPageBuilder::create()
 *         ->accessBlocked('Operation not permitted.')
 *         ->guard(fn() => $user->isActive())
 *         ->guard(fn() => !$user->hasPendingDispute())
 *         ->renderIfGuardFails();
 *     // execution continues here when all guards pass
 *
 *  C) Multi-step setup / progress page:
 *
 *     EmergencyPageBuilder::create()
 *         ->title('Initial Setup')
 *         ->severity('SETUP')
 *         ->step('Reading environment template',  fn() => loadTemplate())
 *         ->step('Generating application key',    fn() => generateKey())
 *         ->step('Writing .env',                  fn() => writeEnv())
 *         ->step('Creating database file',        fn() => createDb())
 *         ->step('Scheduling migrations', pending: true)
 *         ->submitStep('Open Application', '/')
 *         ->render();
 *
 *     ✓ marks passed steps, ✕ marks failed steps (with error detail),
 *     ⋯ marks pending steps.  submitStep() link only appears when
 *     every non-pending step returned true.
 *
 *  D) Inline submit-button gating (inside a form controller):
 *
 *     echo EmergencyPageBuilder::gatedSubmitButton(
 *         label:  'Proceed to Payment',
 *         checks: [
 *             fn() => $user->hasVerifiedEmail(),
 *             fn() => $cart->isValid(),
 *         ],
 *     );
 *
 * ═══════════════════════════════════════════════════════════════════
 */


// ═══════════════════════════════════════════════════════════════════
//  § 1  EmergencyPageBuilder
// ═══════════════════════════════════════════════════════════════════

final class EmergencyPageBuilder
{
    // ── Page identity ─────────────────────────────────────────────
    private string  $title         = 'System Error';
    private string  $message       = '';
    private int     $code          = 500;
    private string  $severity      = 'CRITICAL';
    private string  $systemState   = '';
    private string  $footerMessage = '';
    private ?string $logBasePath   = null;

    // ── Components ────────────────────────────────────────────────

    /** @var list<array{text:string, href:string, extraCss:string}> */
    private array $buttons = [];

    /** @var list<string> */
    private array $htmlComponents = [];

    // ── Guards ────────────────────────────────────────────────────

    /** @var list<\Closure():bool> */
    private array $guards = [];

    // ── Steps ─────────────────────────────────────────────────────

    /**
     * Each step:
     *   label   — human-readable description shown in the page
     *   closure — executed at render time; null = pending (no execution)
     *   pending — true forces the ⋯ "deferred" display regardless of closure
     *
     * Closure contract:
     *   return true    → step ✓ passed
     *   return false   → step ✕ failed (generic message)
     *   return string  → step ✕ failed with that specific message
     *   throw          → step ✕ failed with the exception message
     *
     * @var list<array{label:string, closure:\Closure|null, pending:bool}>
     */
    private array $steps = [];

    /**
     * The submit step: the "proceed" action rendered as a plain <a>
     * link — no JS, no reload — only when every non-pending step
     * passed. When any step failed, a neutral "incomplete" notice
     * appears instead and the link is never emitted.
     *
     * @var array{label:string, href:string, extraCss:string}|null
     */
    private ?array $submitStep = null;

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

    // ── Component builders ────────────────────────────────────────

    /**
     * Add an action button to the page.
     *
     * $href is a real navigation target — a server route, external
     * URL, or mailto: address. No javascript: URIs.
     */
    public function addButton(
        string $text,
        string $href     = '/',
        string $extraCss = '',
    ): self {
        $this->buttons[] = [
            'text'     => $text,
            'href'     => $href,
            'extraCss' => $extraCss,
        ];
        return $this;
    }

    /**
     * Append a raw, pre-escaped HTML fragment inside the card body.
     * You are responsible for escaping any dynamic content inside $html.
     */
    public function addHtmlComponent(string $html): self
    {
        $this->htmlComponents[] = $html;
        return $this;
    }

    // ── Steps ─────────────────────────────────────────────────────

    /**
     * Add a named step, optionally with a closure and/or pending flag.
     *
     * Steps are registered here and executed lazily inside ->render().
     * This keeps the chain declaration-like and human-readable:
     *
     *   ->step('Reading template',    fn() => readTemplate())
     *   ->step('Generating key',      fn() => generateKey())
     *   ->step('Writing .env',        fn() => writeEnv())
     *   ->step('Scheduling migrations', pending: true)
     *
     * $pending = true  Marks the step as deferred. No closure is
     *                  executed; the step is shown as ⋯ and does not
     *                  affect whether submitStep() is displayed.
     *
     * @param (\Closure():bool)|(\Closure():string)|null $closure
     */
    public function step(
        string    $label,
        ?\Closure $closure = null,
        bool      $pending = false,
    ): self {
        $this->steps[] = [
            'label'   => $label,
            'closure' => $closure,
            'pending' => $pending,
        ];
        return $this;
    }

    /**
     * Register the terminal action for a step-based page.
     *
     * Renders as a plain <a> link (no JS) pointing to $href — a real
     * server route that the next request hits normally. The link is
     * only emitted when every non-pending step returned true.
     *
     * If any step failed the link is suppressed and a neutral
     * "Setup incomplete" notice appears instead, so the user cannot
     * navigate forward until the system is actually ready.
     *
     * Only one submitStep per builder — last call wins.
     */
    public function submitStep(
        string $label,
        string $href     = '/',
        string $extraCss = '',
    ): self {
        $this->submitStep = [
            'label'    => $label,
            'href'     => $href,
            'extraCss' => $extraCss,
        ];
        return $this;
    }

    // ── Guards ────────────────────────────────────────────────────

    /**
     * Register a boolean closure guard.
     *
     * Guards are evaluated lazily when ->renderIfGuardFails() is
     * called. Returning false triggers an immediate render+terminate.
     * Returning true is a no-op and the chain continues.
     *
     * @param \Closure():bool $check
     */
    public function guard(\Closure $check): self
    {
        $this->guards[] = $check;
        return $this;
    }

    /**
     * Evaluate all registered guards in order.
     * Renders and terminates on the first failure.
     * Returns $this when all guards pass so execution can continue.
     */
    public function renderIfGuardFails(): self
    {
        foreach ($this->guards as $guard) {
            if (!$guard()) {
                $this->render();
                // render() is `never` — unreachable, satisfies analysers
            }
        }
        return $this;
    }

    // ── Semantic presets ──────────────────────────────────────────

    /** Server-side validation failure. */
    public function validationFailed(string $detail, int $code = 422): self
    {
        return $this
            ->title('Validation Failed')
            ->message($detail)
            ->severity('WARNING')
            ->code($code);
    }

    /** Security block / operation denied. */
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

    /** Rate limit exceeded. */
    public function rateLimited(string $detail = 'Please slow down and try again shortly.'): self
    {
        return $this
            ->title('Too Many Requests')
            ->message($detail)
            ->severity('WARNING')
            ->code(429)
            ->footer('RATE LIMIT REACHED');
    }

    /** Scheduled maintenance / planned downtime. */
    public function maintenance(string $detail = 'The system is being updated. Please check back shortly.'): self
    {
        return $this
            ->title('Scheduled Maintenance')
            ->message($detail)
            ->severity('INFO')
            ->code(503);
    }

    // ── Static inline submit-gating ───────────────────────────────

    /**
     * Emit a submit button HTML string gated by server-side closures.
     *
     * If every closure returns true, a real <button type="submit"> is
     * emitted. If any returns false, a visually identical but disabled
     * element is emitted instead — the server never delivers a working
     * submit to a user who should not proceed.
     *
     * Intended for inline use inside form-rendering controllers, not
     * as part of a full-page render.
     *
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
            . '%s'
            . '</button>',
            htmlspecialchars($name,     ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($value,    ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($accent,   ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($accent,   ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($extraCss, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($label,    ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        );
    }

    // ── Render ────────────────────────────────────────────────────

    /**
     * Execute all steps (if any), build the full-page response,
     * set the HTTP status code, and terminate.
     *
     * This is the only `never` exit point in the builder.
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

        // ── Palette ───────────────────────────────────────────────
        [$accent, $accentDim, $accentBorder, $defaultState, $defaultFooter, $iconSvg]
            = self::palette($this->severity);

        $resolvedState  = $this->systemState    !== '' ? $this->systemState    : $defaultState;
        $resolvedFooter = $this->footerMessage  !== '' ? $this->footerMessage  : $defaultFooter;

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
                    $allPassed  = false;
                    $eDetail    = htmlspecialchars(
                        is_string($result) ? $result : '',
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8',
                    );
                    $stepsHtml .= self::stepRow($eLabel, 'fail', $eDetail, $accent);
                }
            }

            // ── submitStep: link or blocked notice ────────────────
            if ($this->submitStep !== null) {
                if ($allPassed) {
                    $eLabel    = htmlspecialchars($this->submitStep['label'],    ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $eHref     = htmlspecialchars($this->submitStep['href'],     ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $eExtraCss = htmlspecialchars($this->submitStep['extraCss'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                    $stepsHtml .= sprintf(
                        '<div class="submit-row">'
                        . '<a href="%s" class="proceed-btn" style="border-color:%s;color:%s;%s">%s</a>'
                        . '</div>',
                        $eHref,
                        $accent,
                        $accent,
                        $eExtraCss,
                        $eLabel,
                    );
                } else {
                    $stepsHtml .= '<div class="submit-row submit-blocked">'
                        . 'Setup incomplete — correct the errors above before continuing.'
                        . '</div>';
                }
            }

            $stepsHtml .= '</div>';
        }

        // ── Escape core strings ───────────────────────────────────
        $eMessage  = htmlspecialchars($this->message,   ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
                $accent,
                $accent,
                htmlspecialchars($btn['extraCss'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                htmlspecialchars($btn['text'],     ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            );
        }

        $extraHtml = implode("\n", $this->htmlComponents);
        $msgBlock  = $eMessage !== ''
            ? "<div class=\"msg-block\">{$eMessage}</div>"
            : '';

        // ── Emit ──────────────────────────────────────────────────
        http_response_code($this->code);

        echo self::buildDocument(
            eState:       $eState,
            eTitle:       $eTitle,
            eSeverity:    $eSeverity,
            eFooter:      $eFooter,
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
        );

        exit(1);
    }

    // ── Internal helpers ──────────────────────────────────────────

    /**
     * Render a single step row.
     * $status: 'ok' | 'fail' | 'pending'
     */
    private static function stepRow(
        string $eLabel,
        string $status,
        string $eDetail,
        string $accent,
    ): string {
        [$icon, $color] = match ($status) {
            'ok'    => ['✓', $accent],
            'fail'  => ['✕', '#ff3333'],
            default => ['⋯', '#555'],
        };

        $detail = $eDetail !== ''
            ? "<span class=\"step-detail\">{$eDetail}</span>"
            : '';

        return sprintf(
            '<div class="step step-%s">'
            . '<span class="step-icon" style="color:%s">%s</span>'
            . '<span class="step-label">%s%s</span>'
            . '</div>',
            htmlspecialchars($status, ENT_QUOTES, 'UTF-8'),
            $color,
            $icon,
            $eLabel,
            $detail,
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
    ): string {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <meta name="robots" content="noindex,nofollow"/>
  <title>{$eState}</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    ::selection, ::-moz-selection { background: transparent; }

    html, body {
      user-select: none;
      -webkit-user-select: none;
      -webkit-touch-callout: none;
      pointer-events: none;
    }

    /* Interactive elements always receive pointer events */
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

    /* ── Card ──────────────────────────────────────────────────── */
    .card {
      max-width: 680px;
      width: 100%;
      background: #0d0d0d;
      border: 1px solid #1a1a1a;
      overflow: hidden;
      box-shadow: 0 4px 32px rgba(0,0,0,.85);
    }

    /* ── Header ────────────────────────────────────────────────── */
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
      font-size: .68rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .08em;
      color: #fff;
    }
    .header-incident {
      flex: 1;
      text-align: center;
      color: #555;
      font-size: .65rem;
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
      gap: .4rem;
    }
    .severity-icon        { width: 15px; height: 15px; flex-shrink: 0; }
    .severity-icon svg    { width: 100%; height: 100%; display: block; }
    .severity-label {
      color: {$accent};
      font-weight: 700;
      font-size: .68rem;
      text-transform: uppercase;
      letter-spacing: .08em;
    }

    /* ── Body ──────────────────────────────────────────────────── */
    .card-body { padding: 1.25rem 1rem; }

    .identity            { margin-bottom: 1rem; text-align: center; }
    .identity picture img {
      max-width: 200px;
      width: 100%;
      height: auto;
      display: block;
      margin: 0 auto .85rem;
      opacity: .85;
    }
    .incident-title {
      font-size: .8rem;
      font-weight: 600;
      color: {$accent};
      text-transform: uppercase;
      letter-spacing: .1em;
    }

    /* ── Message block ─────────────────────────────────────────── */
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

    /* ── Steps ─────────────────────────────────────────────────── */
    .steps {
      margin: .85rem 0;
      display: flex;
      flex-direction: column;
      gap: .3rem;
    }
    .step {
      display: flex;
      align-items: flex-start;
      gap: .55rem;
      font-size: .78rem;
      padding: .4rem .45rem;
      border-radius: 2px;
    }
    .step-ok      { background: rgba(59,130,246,.04); }
    .step-fail    { background: rgba(255,51,51,.05); }
    .step-pending { background: transparent; }

    .step-icon {
      font-family: monospace;
      font-size: .82rem;
      line-height: 1.35;
      flex-shrink: 0;
      width: 1rem;
      text-align: center;
    }
    .step-label  { color: #bbb; line-height: 1.4; }
    .step-detail {
      display: block;
      margin-top: .2rem;
      font-size: .69rem;
      color: #ff5555;
      font-family: 'Courier New', monospace;
    }

    /* ── Submit step ───────────────────────────────────────────── */
    .submit-row {
      margin-top: 1rem;
      padding-top: .85rem;
      border-top: 1px solid #1e1e1e;
    }
    .submit-blocked {
      font-size: .7rem;
      color: #444;
      text-transform: uppercase;
      letter-spacing: .06em;
    }
    .proceed-btn {
      display: inline-block;
      padding: .55rem 1.4rem;
      background: transparent;
      border: 1px solid {$accent};
      color: {$accent};
      font-family: inherit;
      font-size: .73rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .08em;
      text-decoration: none;
      transition: background .14s;
    }
    .proceed-btn:hover { background: {$accentDim}; }

    /* ── Action buttons ────────────────────────────────────────── */
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
      border: 1px solid;
      font-family: inherit;
      font-size: .72rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .08em;
      text-decoration: none;
      transition: background .14s;
    }
    .action-btn:hover { background: {$accentDim}; }

    /* ── Footer ────────────────────────────────────────────────── */
    .card-footer {
      padding: .65rem 1rem;
      text-align: center;
      font-size: .65rem;
      color: #555;
      text-transform: uppercase;
      letter-spacing: .08em;
      background: #080808;
      border-top: 1px solid #1a1a1a;
    }

    /* ── Timestamp ─────────────────────────────────────────────── */
    .timestamp-bar {
      margin-top: 1.25rem;
      text-align: center;
      font-size: .62rem;
      color: rgba(255,255,255,.25);
      font-family: 'Courier New', monospace;
      letter-spacing: .05em;
    }

    /* ── Responsive ────────────────────────────────────────────── */
    @media (max-width: 480px) {
      .identity picture img  { max-width: 140px; }
      .card-body             { padding: .9rem .75rem; }
      .msg-block             { font-size: .74rem; padding: .65rem .75rem; }
      .card-footer           { font-size: .6rem; }
      .timestamp-bar         { font-size: .57rem; margin-top: .85rem; }
      .header-incident       { display: none; }
      .step                  { font-size: .74rem; }
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
          <source srcset="/logo-dark.png" media="(prefers-color-scheme:dark)"/>
          <img src="/src-app/logo-light-mode.png" alt="System" loading="eager"/>
        </picture>
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
//  § 2  ServerSideValidator
// ═══════════════════════════════════════════════════════════════════

/**
 * Chainable server-side validator.
 *
 * String rules:
 *   required | email | url | numeric | integer
 *   min:N | max:N | min_value:N | max_value:N
 *   in:a,b,c | not_in:a,b,c | regex:/pattern/
 *
 * Closure rules receive ($value, $fullDataArray) and must return:
 *   true     → pass
 *   false    → fail with generic message
 *   string   → fail with that specific message
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
     * @param string|\Closure(mixed $value, array<string,mixed> $data): (bool|string) $rule
     */
    public function field(
        string          $field,
        string|\Closure $rule,
        string          $label = '',
    ): self {
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
            $field = $entry['field'];
            $label = $entry['label'];
            $value = $this->data[$field] ?? null;
            $rule  = $entry['rule'];

            if ($rule instanceof \Closure) {
                $result = $rule($value, $this->data);
                if ($result !== true) {
                    $this->errors[] = is_string($result)
                        ? $result
                        : "'{$label}' failed validation.";
                }
                continue;
            }

            foreach (explode('|', $rule) as $token) {
                $error = self::applyToken($token, $label, $value);
                if ($error !== null) {
                    $this->errors[] = $error;
                    break; // one error per field
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

    /**
     * @param \Closure(string $first, list<string> $all): void $callback
     */
    public function onFail(\Closure $callback): self
    {
        if ($this->fails()) {
            $callback($this->firstError(), $this->errors());
        }
        return $this;
    }

    /**
     * Render an EmergencyPageBuilder page and terminate if validation fails.
     *
     * @param \Closure(EmergencyPageBuilder $b, string $firstError): void|null $customise
     */
    public function renderOnFail(?\Closure $customise = null): self
    {
        if ($this->fails()) {
            $builder = EmergencyPageBuilder::create()
                ->validationFailed($this->firstError());

            if ($customise !== null) {
                $customise($builder, $this->firstError());
            }

            $builder->render();
        }

        return $this;
    }

    // ── Rule engine ───────────────────────────────────────────────

    private static function applyToken(string $token, string $label, mixed $value): ?string
    {
        $str = is_string($value) ? trim($value) : (string) ($value ?? '');

        [$name, $param] = str_contains($token, ':')
            ? explode(':', $token, 2)
            : [$token, ''];

        return match ($name) {
            'required'  => ($value === null || $value === '' || $value === [])
                               ? "'{$label}' is required."                               : null,
            'email'     => filter_var($value, FILTER_VALIDATE_EMAIL) === false
                               ? "'{$label}' must be a valid email address."             : null,
            'url'       => filter_var($value, FILTER_VALIDATE_URL) === false
                               ? "'{$label}' must be a valid URL."                       : null,
            'numeric'   => !is_numeric($value)
                               ? "'{$label}' must be numeric."                           : null,
            'integer'   => filter_var($value, FILTER_VALIDATE_INT) === false
                               ? "'{$label}' must be an integer."                        : null,
            'min'       => mb_strlen($str) < (int) $param
                               ? "'{$label}' must be at least {$param} characters."      : null,
            'max'       => mb_strlen($str) > (int) $param
                               ? "'{$label}' must not exceed {$param} characters."       : null,
            'min_value' => (float) $value < (float) $param
                               ? "'{$label}' must be at least {$param}."                 : null,
            'max_value' => (float) $value > (float) $param
                               ? "'{$label}' must not exceed {$param}."                  : null,
            'in'        => !in_array($str, explode(',', $param), true)
                               ? "'{$label}' contains an unacceptable value."            : null,
            'not_in'    => in_array($str, explode(',', $param), true)
                               ? "'{$label}' contains a disallowed value."               : null,
            'regex'     => !preg_match($param, $str)
                               ? "'{$label}' format is invalid."                         : null,
            default     => null,
        };
    }
}


// ═══════════════════════════════════════════════════════════════════
//  § 3  HttpClient  +  HttpResponse
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

    // ── Method shortcuts ──────────────────────────────────────────

    public function get(string $url): self    { $this->method = 'GET';    $this->url = $url; return $this; }
    public function post(string $url): self   { $this->method = 'POST';   $this->url = $url; return $this; }
    public function put(string $url): self    { $this->method = 'PUT';    $this->url = $url; return $this; }
    public function patch(string $url): self  { $this->method = 'PATCH';  $this->url = $url; return $this; }
    public function delete(string $url): self { $this->method = 'DELETE'; $this->url = $url; return $this; }

    // ── Body builders ─────────────────────────────────────────────

    public function jsonBody(array $data): self
    {
        $this->rawBody = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $this->headers['Content-Type'] = 'application/json';
        return $this;
    }

    public function formBody(array $data): self
    {
        $this->rawBody = http_build_query($data);
        $this->headers['Content-Type'] = 'application/x-www-form-urlencoded';
        return $this;
    }

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

    public function basicAuth(string $user, string $pass): self
    {
        return $this->withHeader('Authorization', 'Basic ' . base64_encode("{$user}:{$pass}"));
    }

    public function accept(string $mime): self    { return $this->withHeader('Accept', $mime); }
    public function timeout(int $seconds): self   { $this->timeout = max(1, $seconds); return $this; }

    public function withoutSslVerification(): self { $this->verifySsl = false; return $this; }

    // ── Interceptors ──────────────────────────────────────────────

    /**
     * Register a `never` closure triggered when the response status
     * does NOT match $expected. Perfect for inline page renders:
     *
     *   ->expectStatus(200, fn() =>
     *       EmergencyPageBuilder::create()
     *           ->accessBlocked('Upstream denied the request.')
     *           ->render()
     *   )
     *
     * @param \Closure():never $handler
     */
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

    // ── Terminal ──────────────────────────────────────────────────

    public function send(): HttpResponse
    {
        $response = function_exists('curl_init')
            ? $this->sendViaCurl()
            : $this->sendViaStream();

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

    // ── Transport ─────────────────────────────────────────────────

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
            CURLOPT_USERAGENT      => 'WebKernel-HttpClient/3.0',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
        ]);

        if ($this->rawBody !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->rawBody);
        }

        $headerLines = array_map(
            static fn(string $k, string $v): string => "{$k}: {$v}",
            array_keys($this->headers),
            $this->headers,
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
            array_keys($this->headers),
            $this->headers,
        );

        $ctx  = stream_context_create([
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

// ── HttpResponse ──────────────────────────────────────────────────

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

    /**
     * Terminate with an emergency page if the response failed.
     *
     * @param \Closure(HttpResponse $r): never|null $customise
     */
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
//  § 4  Backward-compatible free functions
// ═══════════════════════════════════════════════════════════════════

if (!function_exists('renderCriticalErrorHtml')) {
    /**
     * Original signature preserved for zero-migration compatibility.
     * $autoRefreshSeconds is accepted but ignored — use submitStep()
     * on the builder for controlled navigation instead.
     */
    function renderCriticalErrorHtml(
        string  $title,
        string  $message,
        int     $code               = 500,
        string  $severity           = 'CRITICAL',
        ?string $exception          = null,
        ?string $logBasePath        = null,
        string  $systemState        = '',
        string  $footerMessage      = '',
        int     $autoRefreshSeconds = 0,
        bool    $showRefreshButton  = false,
    ): never {
        $builder = EmergencyPageBuilder::create()
            ->title($title)
            ->message($message)
            ->code($code)
            ->severity($severity)
            ->logTo($logBasePath)
            ->systemState($systemState)
            ->footer($footerMessage);

        if ($showRefreshButton) {
            $builder->addButton('Reload', '/');
        }

        $builder->render();
    }
}

if (!function_exists('logCriticalIncident')) {
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
