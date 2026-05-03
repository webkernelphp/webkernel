{{-- bootstrap/webkernel/backend/quick_touch/quick_touch/partials/styles.blade.php --}}
<style>
/* ═══════════════════════════════════════════════════════════════════════════
   Webkernel QuickTouch — structural CSS
   All colours use CSS custom-properties so they inherit Filament's dark-mode
   class (.dark) and the OS media-query automatically.
   ═══════════════════════════════════════════════════════════════════════════ */

:root {
    /* geometry */
    --wkt-size:       52px;
    --wkt-peek:       10px;
    --wkt-panel-w:    320px;
    --wkt-radius:     14px;
    --wkt-z:          9999;
    --wkt-ease:       220ms cubic-bezier(0.4,0,0.2,1);
    --wkt-peek-thresh: 30px;

    /* light palette — will be overridden by .dark / prefers-color-scheme */
    --wkt-bg:         rgba(255,255,255,0.97);
    --wkt-border:     rgba(0,0,0,0.08);
    --wkt-shadow:     0 8px 40px rgba(0,0,0,0.14), 0 1.5px 4px rgba(0,0,0,0.08);
    --wkt-text:       #111827;
    --wkt-muted:      #6b7280;
    --wkt-accent:     #256ffc;
    --wkt-hover:      rgba(0,0,0,0.045);
    --wkt-divider:    rgba(0,0,0,0.07);

    /* dark overrides */
    --wkt-bg-dark:      rgba(17,20,26,0.97);
    --wkt-border-dark:  rgba(255,255,255,0.10);
    --wkt-shadow-dark:  0 8px 40px rgba(0,0,0,0.55), 0 1.5px 4px rgba(0,0,0,0.28);
    --wkt-text-dark:    #f0f0f2;
    --wkt-muted-dark:   #9ca3af;
    --wkt-hover-dark:   rgba(255,255,255,0.06);
    --wkt-divider-dark: rgba(255,255,255,0.07);
}

/* OS-level dark */
@media (prefers-color-scheme: dark) {
    :root {
        --wkt-bg:      var(--wkt-bg-dark);
        --wkt-border:  var(--wkt-border-dark);
        --wkt-shadow:  var(--wkt-shadow-dark);
        --wkt-text:    var(--wkt-text-dark);
        --wkt-muted:   var(--wkt-muted-dark);
        --wkt-hover:   var(--wkt-hover-dark);
        --wkt-divider: var(--wkt-divider-dark);
    }
}

/* Filament .dark class override */
.dark {
    --wkt-bg:      var(--wkt-bg-dark);
    --wkt-border:  var(--wkt-border-dark);
    --wkt-shadow:  var(--wkt-shadow-dark);
    --wkt-text:    var(--wkt-text-dark);
    --wkt-muted:   var(--wkt-muted-dark);
    --wkt-hover:   var(--wkt-hover-dark);
    --wkt-divider: var(--wkt-divider-dark);
}

/* ── root wrapper ─────────────────────────────────────────────────────── */
#webkernel-touch-root {
    position: fixed;
    z-index: var(--wkt-z);
    touch-action: none;
    user-select: none;
    -webkit-user-select: none;
}

/* ── floating button ──────────────────────────────────────────────────── */
#webkernel-touch-btn {
    width:  var(--wkt-size);
    height: var(--wkt-size);
    border-radius: 50%;
    background:  var(--wkt-bg);
    border:      1px solid var(--wkt-border);
    box-shadow:  var(--wkt-shadow);
    display:     flex;
    align-items: center;
    justify-content: center;
    cursor:  pointer;
    padding: 0;
    outline: none;
    transition: transform var(--wkt-ease), box-shadow var(--wkt-ease), opacity var(--wkt-ease);
    -webkit-tap-highlight-color: transparent;
}
#webkernel-touch-btn:hover  { transform: scale(1.07); box-shadow: var(--wkt-shadow), 0 0 0 4px rgba(37,111,252,0.13); }
#webkernel-touch-btn:active { transform: scale(0.96); }
#webkernel-touch-btn.wkt-idle { opacity: 0.52; }
#webkernel-touch-btn.wkt-idle:hover { opacity: 1; }

/* ── peek (button hidden on edge, sliver visible) ─────────────────────── */
#webkernel-touch-root[data-peek="left"]   { transform: translateX(calc(-1*(var(--wkt-size) - var(--wkt-peek)))); }
#webkernel-touch-root[data-peek="right"]  { transform: translateX(calc(var(--wkt-size) - var(--wkt-peek))); }
#webkernel-touch-root[data-peek="top"]    { transform: translateY(calc(-1*(var(--wkt-size) - var(--wkt-peek)))); }
#webkernel-touch-root[data-peek="bottom"] { transform: translateY(calc(var(--wkt-size) - var(--wkt-peek))); }
#webkernel-touch-root[data-peek] { transition: transform 250ms cubic-bezier(0.4,0,0.2,1); }
#webkernel-touch-root[data-peek]:hover,
#webkernel-touch-root[data-peek]:focus-within { transform: none; }

/* ── slide-over panel ─────────────────────────────────────────────────── */
#webkernel-touch-panel {
    position:       absolute;
    width:          var(--wkt-panel-w);
    max-height:     calc(100dvh - 32px);
    background:     var(--wkt-bg);
    border:         1px solid var(--wkt-border);
    box-shadow:     var(--wkt-shadow);
    border-radius:  var(--wds-radius-container);
    overflow:       hidden;
    display:        none;
    flex-direction: column;
    animation:      wkt-panel-in 180ms cubic-bezier(0.34,1.56,0.64,1) forwards;
}
#webkernel-touch-panel.wkt-panel-open { display: flex; }

@keyframes wkt-panel-in {
    from { opacity:0; transform:scale(0.88) translateY(8px); }
    to   { opacity:1; transform:scale(1)    translateY(0); }
}

/* ── Tabs (compact) ─────────────────────────────────────────── */

.wkt-compact-tabs .fi-tabs {
  gap: 4px;          /* minimal spacing between tabs */
}
.wkt-compact-tabs .fi-tabs-item {
  padding: 2px 6px;  /* very tight padding */
  font-size: 12px;   /* smaller text */
  line-height: 1.2;  /* reduced line height */
}
.wkt-compact-tabs .fi-tabs-item [id^="wkt-tab-btn"] {
  margin: 0;         /* no extra margins */
}

/* ── scroll area inside panel ─────────────────────────────────────────── */
.wkt-scroll {
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--wkt-divider) transparent;
    flex: 1 1 auto;
    min-height: 0;
}
.wkt-scroll::-webkit-scrollbar       { width: 4px; }
.wkt-scroll::-webkit-scrollbar-thumb { background: var(--wkt-divider); border-radius: 4px; }

/* ── quick-action grid ────────────────────────────────────────────────── */
.wkt-quick-grid {
    display: grid;
    grid-template-columns: repeat(4,1fr);
    gap: 2px;
    padding: 8px 8px 10px;
}
.wkt-quick-btn {
    display:        flex;
    flex-direction: column;
    align-items:    center;
    gap:            5px;
    padding:        8px 4px;
    border-radius:  10px;
    cursor:         pointer;
    border:         none;
    background:     none;
    color:          var(--wkt-text);
    font-size:      10px;
    font-weight:    500;
    text-align:     center;
    transition:     background var(--wkt-ease);
    -webkit-tap-highlight-color: transparent;
}
.wkt-quick-btn:hover { background: var(--wkt-hover); }
.wkt-quick-btn svg   { width:20px; height:20px; color:var(--wkt-accent); }

/* ── list items ───────────────────────────────────────────────────────── */
.wkt-item {
    display:flex; align-items:center; gap:10px;
    padding:9px 16px;
    cursor:pointer;
    color:var(--wkt-text); font-size:13.5px;
    border:none; background:none; width:100%;
    text-align:left; text-decoration:none;
    transition: background var(--wkt-ease);
    -webkit-tap-highlight-color: transparent;
}
.wkt-item:hover  { background: var(--wkt-hover); }
.wkt-item:active { background: rgba(37,111,252,0.09); }
.wkt-item-icon  { width:16px; height:16px; flex-shrink:0; color:var(--wkt-muted); display:flex; align-items:center; }
.wkt-item-label { flex:1; font-weight:400; }
.wkt-item-badge { font-size:10px; padding:1px 6px; border-radius:20px; background:rgba(37,111,252,0.12); color:var(--wkt-accent); font-weight:500; }

/* ── divider ──────────────────────────────────────────────────────────── */
.wkt-divider { height:1px; background:var(--wkt-divider); margin:4px 0; }

/* ── section label ────────────────────────────────────────────────────── */
.wkt-section-label {
    font-size:10px; font-weight:600; letter-spacing:0.08em;
    text-transform:uppercase; color:var(--wkt-muted);
    padding:10px 16px 4px;
}

/* ── footer ───────────────────────────────────────────────────────────── */
.wkt-footer {
    border-top:1px solid var(--wkt-divider);
    padding:6px 8px;
    display:flex; gap:4px;
    flex-shrink: 0;
}
.wkt-footer-btn {
    flex:1; display:flex; align-items:center; justify-content:center; gap:5px;
    padding:7px 8px; border-radius:8px; border:none; background:none;
    color:var(--wkt-muted); font-size:11px; font-weight:500;
    cursor:pointer;
    transition: background var(--wkt-ease), color var(--wkt-ease);
    -webkit-tap-highlight-color: transparent;
}
.wkt-footer-btn:hover { background:var(--wkt-hover); color:var(--wkt-text); }
.wkt-footer-btn svg   { width:14px; height:14px; }

/* ── standalone context menu ──────────────────────────────────────────── */
#webkernel-touch-ctx {
    position:fixed; z-index:calc(var(--wkt-z) + 1);
    width:230px;
    background:var(--wkt-bg);
    border:1px solid var(--wkt-border);
    box-shadow:var(--wkt-shadow);
    border-radius:var(--wds-radius-container);
    padding:6px 0;
    display:none; flex-direction:column;
    animation:wkt-panel-in 160ms cubic-bezier(0.34,1.56,0.64,1) forwards;
}
#webkernel-touch-ctx.wkt-ctx-open { display:flex; }

.wkt-ctx-item {
    display:flex; align-items:center; gap:10px;
    padding:8px 14px; cursor:pointer;
    color:var(--wkt-text); font-size:13px;
    border:none; background:none; width:100%;
    text-align:left; text-decoration:none;
    transition:background var(--wkt-ease);
    -webkit-tap-highlight-color:transparent;
}
.wkt-ctx-item:hover { background:var(--wkt-hover); }
.wkt-ctx-item-icon  { width:15px; height:15px; color:var(--wkt-muted); flex-shrink:0; }
.wkt-ctx-divider    { height:1px; background:var(--wkt-divider); margin:4px 12px; }

/* ── user chip in header ──────────────────────────────────────────────── */
.wkt-user-chip {
    display:flex; align-items:center; gap:8px;
    padding:10px 14px 8px;
    border-bottom:1px solid var(--wkt-divider);
}
.wkt-user-avatar {
    width:28px; height:28px; border-radius:50%;
    background:var(--wkt-accent); color:#fff;
    font-size:11px; font-weight:600;
    display:flex; align-items:center; justify-content:center;
    flex-shrink:0;
}
.wkt-user-name  { font-size:12px; font-weight:600; color:var(--wkt-text); line-height:1.3; }
.wkt-user-email { font-size:10px; color:var(--wkt-muted); line-height:1.3; }
</style>
