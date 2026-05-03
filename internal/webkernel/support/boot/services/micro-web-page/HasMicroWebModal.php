<?php declare(strict_types=1);
// ═══════════════════════════════════════════════════════════════════
//  Trait: HasMicroWebModal
//  Self-contained modal overlay fragment.
//  Renders as an HTML fragment — embed it anywhere in your views.
//  The modal's theme is synced from the parent page via MutationObserver.
// ═══════════════════════════════════════════════════════════════════

trait HasMicroWebModal
{
    // ── Modal-specific buttons ────────────────────────────────────────────

    /** @var list<array{text:string, href:string, style:string}> */
    private array $modalButtons = [];

    /**
     * Add a button inside the modal footer.
     *
     * @param string $style 'primary' | 'cancel' | 'destructive'
     */
    public function modalButton(string $text, string $href, string $style = 'primary'): static
    {
        $this->modalButtons[] = ['text' => $text, 'href' => $href, 'style' => $style];
        return $this;
    }

    // ── Render ────────────────────────────────────────────────────────────

    public function renderModal(): string
    {
        [$accent, $accentDim, $accentBorder,, , $iconSvg] = self::palette($this->severity);

        $eTitle   = htmlspecialchars($this->title,   ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $msgBlock = $this->message !== ''
            ? htmlspecialchars($this->message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            : '';

        $badgeColor = match (strtoupper($this->severity)) {
            'WARNING'       => '#f59e0b',
            'INFO', 'SETUP' => '#3b82f6',
            default         => '#ef4444',
        };

        // ── Modal buttons ─────────────────────────────────────────────────
        $buttonsHtml = '';
        foreach ($this->modalButtons as $btn) {
            [$bBorder, $bText] = match ($btn['style']) {
                'destructive' => ['#ef4444',            '#ef4444'],
                'cancel'      => ['var(--wkm-border)',   'var(--wkm-muted)'],
                default       => [$accent,              $accent],
            };
            $buttonsHtml .= sprintf(
                '<a href="%s" class="wkm-btn" style="border-color:%s;color:%s;">%s</a>',
                htmlspecialchars($btn['href'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                $bBorder, $bText,
                htmlspecialchars($btn['text'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            );
        }
        if ($buttonsHtml === '') {
            $buttonsHtml = sprintf(
                '<a href="/" class="wkm-btn" style="border-color:%s;color:%s;">OK</a>',
                $accent, $accent
            );
        }

        $eSeverity = htmlspecialchars($this->severity, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return <<<HTML
<div id="wk-modal-overlay"
     onclick="if(event.target===this)this.remove()"
     style="position:fixed;inset:0;z-index:9999;display:flex;
            align-items:center;justify-content:center;padding:1rem;
            background:rgba(0,0,0,.55);
            backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);">
<style>
  /* ── Scoped vars: dark defaults, light override from data-wkm-theme ── */
  #wk-modal-overlay {
    font-family:'Space Grotesk',system-ui,sans-serif;
    --wkm-bg:      #000000;
    --wkm-surface: #080808;
    --wkm-card:    #0d0d0d;
    --wkm-border:  #1a1a1a;
    --wkm-fg:      #f0f0f0;
    --wkm-fg-dim:  #d0d0d0;
    --wkm-muted:   #555555;
  }
  #wk-modal-overlay[data-wkm-theme="light"] {
    --wkm-bg:      #f9fafb;
    --wkm-surface: #f3f4f6;
    --wkm-card:    #ffffff;
    --wkm-border:  #e5e7eb;
    --wkm-fg:      #111827;
    --wkm-fg-dim:  #374151;
    --wkm-muted:   #6b7280;
  }
  .wkm-card {
    background:var(--wkm-card);border:1px solid var(--wkm-border);
    width:100%;max-width:440px;overflow:hidden;
    box-shadow:0 24px 80px rgba(0,0,0,.55);
    animation:wkm-in .18s cubic-bezier(.34,1.56,.64,1) both;
  }
  .wkm-header {
    background:var(--wkm-surface);border-bottom:1px solid var(--wkm-border);
    padding:.6rem 1rem;display:flex;align-items:center;gap:.6rem;
  }
  .wkm-header-sev {
    flex:1;font-size:.62rem;font-weight:700;text-transform:uppercase;
    letter-spacing:.08em;color:{$badgeColor};
  }
  .wkm-header-close {
    background:none;border:1px solid var(--wkm-border);color:var(--wkm-muted);
    cursor:pointer;padding:.22rem .55rem;font-size:.6rem;text-transform:uppercase;
    letter-spacing:.06em;font-family:inherit;transition:border-color .12s,color .12s;
  }
  .wkm-header-close:hover { border-color:#ef4444;color:#ef4444; }
  .wkm-body { padding:1.25rem 1rem;text-align:center; }
  .wkm-icon { width:36px;height:36px;margin:0 auto .85rem;opacity:.9; }
  .wkm-icon svg { width:100%;height:100%;display:block; }
  .wkm-title {
    font-size:.85rem;font-weight:700;color:var(--wkm-fg);
    text-transform:uppercase;letter-spacing:.08em;margin-bottom:.5rem;
  }
  .wkm-msg {
    background:{$accentDim};border-left:2px solid {$accentBorder};
    padding:.7rem .85rem;margin:.7rem 0 1.1rem;
    font-size:.78rem;line-height:1.6;color:var(--wkm-fg-dim);
    text-align:left;white-space:pre-wrap;word-break:break-word;
  }
  .wkm-actions { display:flex;gap:.5rem;justify-content:center;flex-wrap:wrap; }
  .wkm-btn {
    display:inline-flex;align-items:center;justify-content:center;
    min-width:80px;padding:.45rem 1.1rem;background:transparent;border:1px solid;
    font-family:inherit;font-size:.68rem;font-weight:600;
    text-transform:uppercase;letter-spacing:.07em;text-decoration:none;
    transition:background .12s;
  }
  .wkm-footer {
    background:var(--wkm-surface);border-top:1px solid var(--wkm-border);
    padding:.45rem 1rem;text-align:center;
    font-size:.6rem;color:var(--wkm-muted);
    text-transform:uppercase;letter-spacing:.07em;
  }
  @keyframes wkm-in { from{opacity:0;transform:scale(.94) translateY(8px)} to{opacity:1;transform:none} }
</style>
<div class="wkm-card" role="alertdialog" aria-modal="true">
  <div class="wkm-header">
    <span class="wkm-header-sev">{$eSeverity}</span>
    <button class="wkm-header-close"
            onclick="document.getElementById('wk-modal-overlay').remove()">Close</button>
  </div>
  <div class="wkm-body">
    <div class="wkm-icon">{$iconSvg}</div>
    <div class="wkm-title">{$eTitle}</div>
    <div class="wkm-msg">{$msgBlock}</div>
    <div class="wkm-actions">{$buttonsHtml}</div>
  </div>
  <div class="wkm-footer">WEBKERNEL</div>
</div>
<script>
(function () {
  'use strict';
  var overlay = document.getElementById('wk-modal-overlay');
  if (!overlay) return;
  function syncTheme() {
    var t = document.documentElement.getAttribute('data-wk-theme') || 'dark';
    var isLight = t === 'light'
      || (t === 'system' && window.matchMedia && window.matchMedia('(prefers-color-scheme:light)').matches);
    overlay.setAttribute('data-wkm-theme', isLight ? 'light' : 'dark');
  }
  syncTheme();
  if (window.MutationObserver) {
    new MutationObserver(syncTheme).observe(document.documentElement, {
      attributes: true, attributeFilter: ['data-wk-theme']
    });
  }
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') overlay.remove();
  });
}());
</script>
</div>
HTML;
    }
}
