<?php declare(strict_types=1);
// ═══════════════════════════════════════════════════════════════════
//  Trait: HasMicroWebTheme
//  CSS design tokens, colour palette, and theme-switcher logic.
// ═══════════════════════════════════════════════════════════════════

trait HasMicroWebTheme
{
    // ── Accent / severity palette ─────────────────────────────────────────
    // Returns [ $accent, $accentDim, $accentBorder, $defaultState,
    //           $defaultFooter, $iconSvg ]
    // @return array{string,string,string,string,string,string}
    public static function palette(string $sev): array
    {
        return match (strtoupper($sev)) {
            'INFO', 'SETUP' => [
                '#3b82f6',
                'rgba(59,130,246,.08)',
                '#3b82f6',
                'WEBAPP ENVIRONMENT',
                'PLEASE WAIT — SETUP IN PROGRESS',
                '<svg viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"'
                . ' stroke-linecap="round" stroke-linejoin="round">'
                . '<circle cx="12" cy="12" r="10"/>'
                . '<line x1="12" y1="8" x2="12" y2="12"/>'
                . '<line x1="12" y1="16" x2="12.01" y2="16"/>'
                . '</svg>',
            ],
            'WARNING' => [
                '#f59e0b',
                'rgba(245,158,11,.08)',
                '#f59e0b',
                'SYSTEM WARNING',
                'ACTION MAY BE REQUIRED',
                '<svg viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"'
                . ' stroke-linecap="round" stroke-linejoin="round">'
                . '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>'
                . '<line x1="12" y1="9" x2="12" y2="13"/>'
                . '<line x1="12" y1="17" x2="12.01" y2="17"/>'
                . '</svg>',
            ],
            default => [
                '#ef4444',
                'rgba(239,68,68,.07)',
                '#ef4444',
                'SYSTEM STATE: SEALED',
                'NO FURTHER ACTION IS PERMITTED',
                '<svg viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"'
                . ' stroke-linecap="round" stroke-linejoin="round">'
                . '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>'
                . '<line x1="12" y1="9" x2="12" y2="13"/>'
                . '<line x1="12" y1="17" x2="12.01" y2="17"/>'
                . '</svg>',
            ],
        };
    }

    // ── Inline CSS design tokens ──────────────────────────────────────────
    protected static function cssTokens(): string
    {
        return <<<CSS
/* ── MicroWebPage design tokens ──────────────────────────────────────── */
:root[data-wk-theme="light"] {
  --wk-bg:           #f9fafb;
  --wk-surface:      #f3f4f6;
  --wk-card:         #ffffff;
  --wk-border:       #e5e7eb;
  --wk-fg:           #111827;
  --wk-fg-dim:       #374151;
  --wk-muted:        #6b7280;
  --wk-shadow:       0 4px 32px rgba(0,0,0,.10);
  --wk-radius:       0px;
}
:root[data-wk-theme="dark"],
:root[data-wk-theme="system"],
:root[data-wk-theme=""] {
  --wk-bg:           #000000;
  --wk-surface:      #080808;
  --wk-card:         #0d0d0d;
  --wk-border:       #1a1a1a;
  --wk-fg:           #f0f0f0;
  --wk-fg-dim:       #d0d0d0;
  --wk-muted:        #555555;
  --wk-shadow:       0 4px 32px rgba(0,0,0,.85);
  --wk-radius:       0px;
}
@media (prefers-color-scheme:light) {
  :root[data-wk-theme="system"] {
    --wk-bg:         #f9fafb;
    --wk-surface:    #f3f4f6;
    --wk-card:       #ffffff;
    --wk-border:     #e5e7eb;
    --wk-fg:         #111827;
    --wk-fg-dim:     #374151;
    --wk-muted:      #6b7280;
    --wk-shadow:     0 4px 32px rgba(0,0,0,.10);
    --wk-radius:     0px;
  }
}
CSS;
    }

    // ── Theme-switcher SVG icons ──────────────────────────────────────────
    protected static function iconSun(): string
    {
        if (function_exists('grap_webkernel_icon')) {
            $r = grap_webkernel_icon('sun');
            if ($r !== null) return $r;
        }
        return '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
             . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
             . '<circle cx="12" cy="12" r="5"/>'
             . '<line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>'
             . '<line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>'
             . '<line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>'
             . '<line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>'
             . '<line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>'
             . '<line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>'
             . '</svg>';
    }

    protected static function iconMoon(): string
    {
        if (function_exists('grap_webkernel_icon')) {
            $r = grap_webkernel_icon('moon');
            if ($r !== null) return $r;
        }
        return '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
             . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
             . '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>'
             . '</svg>';
    }

    protected static function iconMonitor(): string
    {
        if (function_exists('grap_webkernel_icon')) {
            $r = grap_webkernel_icon('monitor');
            if ($r !== null) return $r;
        }
        return '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
             . ' stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
             . '<rect x="2" y="3" width="20" height="14" rx="2"/>'
             . '<line x1="8" y1="21" x2="16" y2="21"/>'
             . '<line x1="12" y1="17" x2="12" y2="21"/>'
             . '</svg>';
    }

    // ── Theme-switcher HTML widget ────────────────────────────────────────
    protected static function themeSwitcherHtml(): string
    {
        $sun  = self::iconSun();
        $moon = self::iconMoon();
        $sys  = self::iconMonitor();
        return <<<HTML
<div class="wk-theme-switcher" role="group" aria-label="Color theme">
  <button class="wk-theme-btn" id="wk-tbtn-light"  onclick="wkSetTheme('light')"  title="Light">{$sun}</button>
  <button class="wk-theme-btn" id="wk-tbtn-dark"   onclick="wkSetTheme('dark')"   title="Dark">{$moon}</button>
  <button class="wk-theme-btn" id="wk-tbtn-system" onclick="wkSetTheme('system')" title="System">{$sys}</button>
</div>
HTML;
    }

    // ── Theme-switcher JS (inline, no deps) ───────────────────────────────
    protected static function themeSwitcherJs(string $jsLogoLight = '', string $jsLogoDark = ''): string
    {
        $ll = addslashes($jsLogoLight);
        $ld = addslashes($jsLogoDark);
        return <<<JS
(function () {
  'use strict';
  var KEY    = 'wk-theme';
  var LIGHT  = '{$ll}';
  var DARK   = '{$ld}';
  var root   = document.documentElement;
  var THEMES = ['light','dark','system'];

  function updateLogo(theme) {
    var lightEl = document.getElementById('wk-logo-light');
    var darkEl  = document.getElementById('wk-logo-dark');
    if (!lightEl || !darkEl) return;
    var isDark = theme === 'dark'
      || (theme === 'system' && window.matchMedia && window.matchMedia('(prefers-color-scheme:dark)').matches)
      || (theme !== 'light' && theme !== 'system');
    lightEl.style.display = isDark ? 'none'  : 'block';
    darkEl.style.display  = isDark ? 'block' : 'none';
    if (LIGHT && !isDark) lightEl.src = LIGHT;
    if (DARK  &&  isDark) darkEl.src  = DARK;
  }

  function applyTheme(theme) {
    if (THEMES.indexOf(theme) === -1) theme = 'system';
    root.setAttribute('data-wk-theme', theme);
    THEMES.forEach(function (t) {
      var b = document.getElementById('wk-tbtn-' + t);
      if (b) b.classList.toggle('wk-active', t === theme);
    });
    updateLogo(theme);
    // Keep debug overlay in sync
    var dbg = document.getElementById('wk-debug-overlay');
    if (dbg && dbg.style.display !== 'none') {
      var isLight = theme === 'light'
        || (theme === 'system' && window.matchMedia && window.matchMedia('(prefers-color-scheme:light)').matches);
      dbg.setAttribute('data-wk-theme', isLight ? 'light' : 'dark');
    }
  }

  window.wkSetTheme = function (theme) {
    try { localStorage.setItem(KEY, theme); } catch (e) {}
    applyTheme(theme);
  };

  var stored = 'dark';
  try { stored = localStorage.getItem(KEY) || 'dark'; } catch (e) {}
  applyTheme(stored);

  // Reveal debug button when overlay is in DOM
  document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('wk-debug-overlay')) {
      var btn = document.getElementById('wk-debug-open-btn');
      if (btn) btn.classList.add('wk-visible');
    }
  });

  // React to OS scheme change
  if (window.matchMedia) {
    window.matchMedia('(prefers-color-scheme:dark)').addEventListener('change', function () {
      var cur = 'dark';
      try { cur = localStorage.getItem(KEY) || 'dark'; } catch (e) {}
      if (cur === 'system') applyTheme('system');
    });
  }
}());
JS;
    }
}
