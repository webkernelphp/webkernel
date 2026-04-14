<?php declare(strict_types=1);
// ═══════════════════════════════════════════════════════════════════
//  Trait: HasMicroWebDebug
//  Ignition-style debug overlay (hidden by default, display:none).
//  Opened via wkdOpen() — the "Debug" footer button reveals itself
//  at DOMContentLoaded when the overlay is present in the DOM.
// ═══════════════════════════════════════════════════════════════════

trait HasMicroWebDebug
{
    // ── Exception context ─────────────────────────────────────────────────
    private ?string $exceptionClass   = null;
    private ?string $exceptionMessage = null;
    private ?string $exceptionFile    = null;
    private ?int    $exceptionLine    = null;
    private ?string $exceptionTrace   = null;
    private ?string $requestUrl       = null;
    private ?string $requestMethod    = null;

    /** @var list<array{label:string, value:string}> */
    private array $debugMeta = [];

    /** @var list<array{id:string, label:string, content:string}> */
    private array $debugTabs = [];

    // ── Fluent setters ────────────────────────────────────────────────────

    public function withException(
        string $class, string $message, string $file, int $line, string $trace
    ): static {
        $this->exceptionClass   = $class;
        $this->exceptionMessage = $message;
        $this->exceptionFile    = $file;
        $this->exceptionLine    = $line;
        $this->exceptionTrace   = $trace;
        return $this;
    }

    public function withRequest(string $url, string $method): static
    {
        $this->requestUrl    = $url;
        $this->requestMethod = $method;
        return $this;
    }

    public function withDebugMeta(string $label, string $value): static
    {
        $this->debugMeta[] = ['label' => $label, 'value' => $value];
        return $this;
    }

    public function withDebugTab(string $id, string $label, string $content): static
    {
        $this->debugTabs[] = ['id' => $id, 'label' => $label, 'content' => $content];
        return $this;
    }

    // ── Render ────────────────────────────────────────────────────────────

    public function renderDebugModal(): string
    {
        [$accent] = self::palette($this->severity);

        $badgeColor = match (strtoupper($this->severity)) {
            'WARNING'       => '#f59e0b',
            'INFO', 'SETUP' => '#3b82f6',
            default         => '#ef4444',
        };

        $eExClass   = htmlspecialchars($this->exceptionClass   ?? 'Exception',              ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $eExMsg     = htmlspecialchars($this->exceptionMessage ?? $this->message,            ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $eExFile    = htmlspecialchars($this->exceptionFile    ?? '',                        ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $eExLine    = (string) ($this->exceptionLine ?? 0);
        $eReqUrl    = htmlspecialchars($this->requestUrl    ?? '',                           ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $eReqMethod = htmlspecialchars($this->requestMethod ?? 'GET',                        ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $eCode      = $this->code;
        $eTitle     = htmlspecialchars($this->title,                                         ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $timestamp  = gmdate('Y-m-d\TH:i:s\Z');

        // ── WebKernel version constants ───────────────────────────────────
        $wkVersion    = defined('WEBKERNEL_VERSION')     ? (string) WEBKERNEL_VERSION     : 'unknown';
        $wkBuild      = defined('WEBKERNEL_BUILD')       ? (string) WEBKERNEL_BUILD       : 'unknown';
        $wkSemver     = defined('WEBKERNEL_SEMVER')      ? (string) WEBKERNEL_SEMVER      : 'unknown';
        $wkCodename   = defined('WEBKERNEL_CODENAME')    ? (string) WEBKERNEL_CODENAME    : 'unknown';
        $wkChannel    = defined('WEBKERNEL_CHANNEL')     ? (string) WEBKERNEL_CHANNEL     : 'unknown';
        $wkReleasedAt = defined('WEBKERNEL_RELEASED_AT') ? (string) WEBKERNEL_RELEASED_AT : 'unknown';

        $eWkVersion    = htmlspecialchars($wkVersion,    ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $eWkBuild      = htmlspecialchars($wkBuild,      ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $eWkSemver     = htmlspecialchars($wkSemver,     ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $eWkCodename   = htmlspecialchars($wkCodename,   ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $eWkChannel    = htmlspecialchars($wkChannel,    ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $eWkReleasedAt = htmlspecialchars($wkReleasedAt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // ── Shorten file path ─────────────────────────────────────────────
        $shortFile = $this->exceptionFile ?? '';
        if (strlen($shortFile) > 65) $shortFile = '...' . substr($shortFile, -62);
        $eShortFile = htmlspecialchars($shortFile, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // ── Stack trace lines ─────────────────────────────────────────────
        $traceLines = '';
        if ($this->exceptionTrace !== null) {
            foreach (explode("\n", $this->exceptionTrace) as $i => $rawLine) {
                $eLine = htmlspecialchars(trim($rawLine), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $first = ($i === 0);
                $rowBg = $first ? 'background:rgba(239,68,68,.06);border-left:2px solid #ef4444;padding-left:.4rem;' : '';
                $lc    = $first ? 'var(--wkd-highlight)' : 'var(--wkd-trace)';
                $nc    = $first ? '#ef4444' : 'var(--wkd-line-num)';
                $traceLines .= sprintf(
                    '<div style="display:flex;align-items:flex-start;min-width:0;%s">'
                    . '<span style="flex-shrink:0;width:2.6rem;text-align:right;padding:.16rem .55rem .16rem 0;'
                    . 'font-size:.66rem;color:%s;user-select:none;font-family:\'JetBrains Mono\',monospace;">%d</span>'
                    . '<span style="flex:1;min-width:0;padding:.16rem 0;font-size:.71rem;color:%s;'
                    . 'font-family:\'JetBrains Mono\',\'Fira Code\',monospace;'
                    . 'white-space:pre-wrap;word-break:break-all;">%s</span>'
                    . '</div>',
                    $rowBg, $nc, $i + 1, $lc, $eLine !== '' ? $eLine : '&nbsp;'
                );
            }
        }

        // ── Copy payload ──────────────────────────────────────────────────
        $rawCopy =
            'WEBKERNEL DEBUG REPORT' . "\n"
            . 'Timestamp : ' . $timestamp . ' UTC' . "\n"
            . 'Version   : ' . $wkVersion . '  /  Semver: '  . $wkSemver . "\n"
            . 'Build     : ' . $wkBuild   . '  /  Channel: ' . $wkChannel . "\n"
            . 'Codename  : ' . $wkCodename . '  /  Released: ' . $wkReleasedAt . "\n"
            . str_repeat('-', 60) . "\n"
            . 'Exception : ' . ($this->exceptionClass   ?? 'Exception') . "\n"
            . 'Message   : ' . ($this->exceptionMessage ?? $this->message) . "\n"
            . 'File      : ' . ($this->exceptionFile ?? '') . ':' . ($this->exceptionLine ?? 0) . "\n"
            . 'Request   : ' . ($this->requestMethod  ?? 'GET') . ' ' . ($this->requestUrl ?? '') . "\n"
            . str_repeat('-', 60) . "\n"
            . ($this->exceptionTrace ?? 'No trace available.');
        $jsCopy = str_replace(
            ['\\',    "'",   "\r\n", "\n",  "\r"],
            ['\\\\',  "\\'", '\\n', '\\n',  '\\n'],
            $rawCopy
        );

        // ── Meta row builder ──────────────────────────────────────────────
        $metaRow = static function (string $label, string $value): string {
            $eL = htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $eV = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            return sprintf(
                '<div style="display:flex;gap:.75rem;padding:.45rem 0;'
                . 'border-bottom:1px solid var(--wkd-border);align-items:baseline;">'
                . '<span style="flex-shrink:0;width:5.5rem;font-size:.63rem;color:var(--wkd-label);'
                . 'text-transform:uppercase;letter-spacing:.06em;font-weight:600;">%s</span>'
                . '<span style="flex:1;font-size:.72rem;color:var(--wkd-value);'
                . 'font-family:\'JetBrains Mono\',monospace;word-break:break-all;">%s</span>'
                . '</div>',
                $eL, $eV
            );
        };

        $metaRows = $metaRow('URL',    $eReqUrl)
                  . $metaRow('Method', $eReqMethod)
                  . $metaRow('Code',   (string) $eCode)
                  . $metaRow('Time',   $timestamp . ' UTC');
        foreach ($this->debugMeta as $m) {
            $metaRows .= $metaRow($m['label'], $m['value']);
        }

        $versionRows = $metaRow('Version',  $eWkVersion)
                     . $metaRow('Semver',   $eWkSemver)
                     . $metaRow('Build',    $eWkBuild)
                     . $metaRow('Channel',  $eWkChannel)
                     . $metaRow('Codename', $eWkCodename)
                     . $metaRow('Released', $eWkReleasedAt);

        // ── Notices ───────────────────────────────────────────────────────
        $noticesHtml = '';
        foreach ($this->notices as $n) {
            $c  = match ($n['level']) { 'warning' => '#f59e0b', 'error' => '#ef4444', default => '#3b82f6' };
            $bg = match ($n['level']) { 'warning' => 'rgba(245,158,11,.08)', 'error' => 'rgba(239,68,68,.08)', default => 'rgba(59,130,246,.08)' };
            $eH = htmlspecialchars($n['heading'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $noticesHtml .= sprintf(
                '<div style="background:%s;border-left:2px solid %s;padding:.5rem .8rem;margin-bottom:.45rem;">'
                . '<span style="color:%s;font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;display:block;">%s</span>'
                . '<span style="font-size:.7rem;color:var(--wkd-trace);display:block;margin-top:.12rem;">%s</span>'
                . '</div>',
                $bg, $c, $c, $eH, $n['body']
            );
        }

        // ── Tabs ──────────────────────────────────────────────────────────
        $allTabs = array_merge(
            [
                ['id' => 'trace',   'label' => 'Stack Trace'],
                ['id' => 'request', 'label' => 'Request'],
                ['id' => 'version', 'label' => 'WebKernel'],
            ],
            $this->debugTabs
        );

        $tabButtons = '';
        foreach ($allTabs as $idx => $tab) {
            $eId    = htmlspecialchars($tab['id'],    ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $eLabel = htmlspecialchars($tab['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $tabButtons .= sprintf(
                '<button onclick="wkdTab(\'%s\')" id="wkd-tab-btn-%s" class="wkd-tab-btn%s">%s</button>',
                $eId, $eId, $idx === 0 ? ' wkd-tab-active' : '', $eLabel
            );
        }

        // ── Panels ────────────────────────────────────────────────────────
        $noTrace    = '<div style="padding:1rem;color:var(--wkd-label);font-size:.73rem;">No trace available.</div>';
        $tracePanel = sprintf(
            '<div id="wkd-panel-trace" style="display:block;">'
            . '<div class="wkd-panel-box"><div class="wkd-panel-label">Exception Trace</div>'
            . '<div class="wkd-trace-scroll">%s</div></div></div>',
            $traceLines !== '' ? $traceLines : $noTrace
        );
        $requestPanel = sprintf(
            '<div id="wkd-panel-request" style="display:none;">'
            . '<div class="wkd-panel-box"><div class="wkd-panel-label">Request Details</div>'
            . '<div style="padding:.35rem .8rem;">%s</div></div></div>',
            $metaRows
        );
        $versionPanel = sprintf(
            '<div id="wkd-panel-version" style="display:none;">'
            . '<div class="wkd-panel-box"><div class="wkd-panel-label">WebKernel Runtime</div>'
            . '<div style="padding:.35rem .8rem;">%s</div></div></div>',
            $versionRows
        );

        $tabPanels = $tracePanel . $requestPanel . $versionPanel;
        foreach ($this->debugTabs as $tab) {
            $eId = htmlspecialchars($tab['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $tabPanels .= sprintf('<div id="wkd-panel-%s" style="display:none;">%s</div>', $eId, $tab['content']);
        }

        $incidentId = 'INC-' . strtoupper(substr(hash('sha256', ($this->exceptionMessage ?? '') . microtime(true)), 0, 8));

        return <<<HTML
<div id="wk-debug-overlay"
     style="display:none;position:fixed;inset:0;z-index:99999;
            align-items:flex-start;justify-content:center;
            background:rgba(0,0,0,.72);
            backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);
            padding:1.5rem 1rem;overflow-y:auto;">
<style>
  #wk-debug-overlay {
    font-family:'Space Grotesk',system-ui,sans-serif;
    --wkd-bg:           #0d0d0d;
    --wkd-surface:      #080808;
    --wkd-border:       #1a1a1a;
    --wkd-fg:           #f0f0f0;
    --wkd-label:        #555555;
    --wkd-value:        #d0d0d0;
    --wkd-trace:        #777777;
    --wkd-line-num:     #333333;
    --wkd-highlight:    #fca5a5;
    --wkd-scroll-track: #000000;
    --wkd-scroll-thumb: #2a2a2a;
  }
  #wk-debug-overlay[data-wk-theme="light"] {
    --wkd-bg:           #ffffff;
    --wkd-surface:      #f3f4f6;
    --wkd-border:       #e5e7eb;
    --wkd-fg:           #111827;
    --wkd-label:        #6b7280;
    --wkd-value:        #1f2937;
    --wkd-trace:        #374151;
    --wkd-line-num:     #9ca3af;
    --wkd-highlight:    #991b1b;
    --wkd-scroll-track: #f3f4f6;
    --wkd-scroll-thumb: #d1d5db;
  }
  #wk-debug-overlay * { box-sizing:border-box; }
  .wkd-trace-scroll::-webkit-scrollbar       { width:5px; }
  .wkd-trace-scroll::-webkit-scrollbar-track { background:var(--wkd-scroll-track); }
  .wkd-trace-scroll::-webkit-scrollbar-thumb { background:var(--wkd-scroll-thumb);border-radius:2px; }
  .wkd-trace-scroll { scrollbar-width:thin;scrollbar-color:var(--wkd-scroll-thumb) var(--wkd-scroll-track); }
  @keyframes wkd-in { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:none} }
  .wkd-card {
    width:100%;max-width:940px;background:var(--wkd-bg);
    border:1px solid var(--wkd-border);
    box-shadow:0 32px 100px rgba(0,0,0,.7);
    animation:wkd-in .2s ease both;margin:0 auto;
  }
  .wkd-header {
    background:var(--wkd-surface);border-bottom:1px solid var(--wkd-border);
    padding:.65rem 1.25rem;display:flex;align-items:center;gap:.75rem;
  }
  .wkd-header-left { display:flex;align-items:center;gap:.6rem;min-width:0;flex:1; }
  .wkd-badge {
    font-size:.58rem;font-weight:700;padding:.2rem .45rem;
    text-transform:uppercase;letter-spacing:.06em;color:#fff;
    flex-shrink:0;background:{$badgeColor};
  }
  .wkd-header-title {
    font-size:.65rem;color:var(--wkd-label);font-weight:500;
    text-transform:uppercase;letter-spacing:.06em;flex-shrink:0;
  }
  .wkd-incident {
    font-size:.6rem;color:var(--wkd-line-num);
    font-family:'JetBrains Mono',monospace;
    overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
  }
  .wkd-header-actions { display:flex;align-items:center;gap:.45rem;flex-shrink:0; }
  .wkd-hbtn {
    display:inline-flex;align-items:center;padding:.28rem .65rem;
    background:transparent;border:1px solid var(--wkd-border);color:var(--wkd-label);
    font-size:.6rem;text-decoration:none;text-transform:uppercase;letter-spacing:.06em;
    font-family:inherit;cursor:pointer;transition:border-color .12s,color .12s;
  }
  .wkd-hbtn:hover       { border-color:var(--wkd-trace);color:var(--wkd-fg); }
  .wkd-hbtn-close:hover { border-color:#ef4444;color:#ef4444; }
  .wkd-exception { padding:1.1rem 1.25rem .8rem;border-bottom:1px solid var(--wkd-border); }
  .wkd-ex-class {
    font-size:.6rem;color:{$badgeColor};font-weight:700;
    text-transform:uppercase;letter-spacing:.08em;margin-bottom:.3rem;
  }
  .wkd-ex-msg { font-size:1rem;font-weight:600;color:var(--wkd-fg);line-height:1.4;margin-bottom:.55rem;word-break:break-word; }
  .wkd-ex-loc { font-size:.66rem;color:var(--wkd-label);font-family:'JetBrains Mono','Fira Code',monospace; }
  .wkd-ex-line { color:{$badgeColor};font-weight:600;margin-left:.2rem; }
  .wkd-tabs {
    border-bottom:1px solid var(--wkd-border);padding:0 1.25rem;
    background:var(--wkd-surface);display:flex;gap:0;overflow-x:auto;
  }
  .wkd-tab-btn {
    background:none;border:none;cursor:pointer;padding:.5rem .9rem;
    font-size:.66rem;font-weight:600;letter-spacing:.04em;text-transform:uppercase;
    color:var(--wkd-label);border-bottom:2px solid transparent;white-space:nowrap;
    transition:color .14s,border-color .14s;font-family:inherit;
  }
  .wkd-tab-btn:hover { color:var(--wkd-fg); }
  .wkd-tab-active { color:var(--wkd-fg) !important;border-bottom-color:{$accent} !important; }
  .wkd-panels { padding:1rem 1.25rem;min-height:180px; }
  .wkd-panel-box {
    background:var(--wkd-surface);
    border:1px solid var(--wkd-border);
  }
  .wkd-panel-label {
    padding:.38rem .8rem;background:var(--wkd-bg);border-bottom:1px solid var(--wkd-border);
    font-size:.6rem;color:var(--wkd-label);text-transform:uppercase;letter-spacing:.07em;
  }
  .wkd-trace-scroll {
    height:340px;
    max-height:340px;
    overflow-y:auto;
    overflow-x:hidden;
    padding:.3rem 0;
    min-height:0;
  }
  .wkd-footer {
    background:var(--wkd-surface);border-top:1px solid var(--wkd-border);
    padding:.5rem 1.25rem;display:flex;justify-content:space-between;align-items:center;gap:.5rem;flex-wrap:wrap;
  }
  .wkd-footer-label   { font-size:.6rem;color:var(--wkd-label);text-transform:uppercase;letter-spacing:.06em; }
  .wkd-footer-version { font-size:.6rem;color:var(--wkd-label);font-family:'JetBrains Mono',monospace; }
  .wkd-copy-btn {
    display:inline-flex;align-items:center;gap:.3rem;padding:.28rem .65rem;
    background:transparent;border:1px solid var(--wkd-border);color:var(--wkd-label);
    font-size:.6rem;text-transform:uppercase;letter-spacing:.06em;
    font-family:inherit;cursor:pointer;transition:border-color .12s,color .12s;
  }
  .wkd-copy-btn:hover    { border-color:var(--wkd-trace);color:var(--wkd-fg); }
  .wkd-copy-btn.wkd-copied { border-color:{$accent};color:{$accent}; }
</style>
<div class="wkd-card">
  <div class="wkd-header">
    <div class="wkd-header-left">
      <span class="wkd-badge">{$eCode}</span>
      <span class="wkd-header-title">{$eTitle}</span>
      <span class="wkd-incident">{$incidentId}</span>
    </div>
    <div class="wkd-header-actions">
      <a href="javascript:history.back()" class="wkd-hbtn">Back</a>
      <a href="/" class="wkd-hbtn">Home</a>
      <button class="wkd-hbtn wkd-hbtn-close" onclick="wkdClose()">Dismiss</button>
    </div>
  </div>
  <div class="wkd-exception">
    {$noticesHtml}
    <div class="wkd-ex-class">{$eExClass}</div>
    <div class="wkd-ex-msg">{$eExMsg}</div>
    <div class="wkd-ex-loc">{$eShortFile}<span class="wkd-ex-line">:{$eExLine}</span></div>
  </div>
  <div class="wkd-tabs">{$tabButtons}</div>
  <div class="wkd-panels" id="wkd-panels">{$tabPanels}</div>
  <div class="wkd-footer">
    <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;">
      <span class="wkd-footer-label">WEBKERNEL DEBUG</span>
      <span class="wkd-footer-version">{$eWkVersion} / {$eWkChannel} / {$eWkCodename}</span>
    </div>
    <div style="display:flex;align-items:center;gap:.5rem;">
      <button class="wkd-copy-btn" id="wkd-copy-btn" onclick="wkdCopyError()">Copy Error</button>
      <span class="wkd-footer-version">{$timestamp} UTC</span>
    </div>
  </div>
</div>
<script>
(function () {
  'use strict';

  function ensureOverlay() {
    return document.getElementById('wk-debug-overlay');
  }

  window.wkdOpen = function () {
    var el = ensureOverlay();
    if (!el) return;

    var t = document.documentElement.getAttribute('data-wk-theme') || 'dark';

    var isLight =
      t === 'light' ||
      (t === 'system' &&
        window.matchMedia &&
        window.matchMedia('(prefers-color-scheme: light)').matches);

    el.setAttribute('data-wk-theme', isLight ? 'light' : 'dark');

    el.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  };

  window.wkdClose = function () {
    var el = ensureOverlay();
    if (!el) return;

    el.style.display = 'none';
    document.body.style.overflow = '';
  };

  window.wkdTab = function (id) {
    var container = document.getElementById('wkd-panels');
    if (!container) return;

    container.querySelectorAll(':scope > div').forEach(function (p) {
      p.style.display = 'none';
    });

    var panel = document.getElementById('wkd-panel-' + id);
    if (panel) panel.style.display = 'block';

    document.querySelectorAll('.wkd-tab-btn').forEach(function (b) {
      b.classList.remove('wkd-tab-active');
    });

    var btn = document.getElementById('wkd-tab-btn-' + id);
    if (btn) btn.classList.add('wkd-tab-active');
  };

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') window.wkdClose();
  });

  // CRITICAL: force rebind if overlay injected late
  document.addEventListener('DOMContentLoaded', function () {
    var el = ensureOverlay();
    if (el) {
      // ensure hidden state is consistent
      el.style.display = 'none';
    }
  });
})();
</script>
</div>
HTML;
    }
}
