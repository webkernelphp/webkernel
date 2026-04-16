{{--
    webkernel::panels.layout.css
    ────────────────────────────
    Entry point — includes only, no styles here.
    Injected at PanelsRenderHook::BODY_START (once per request, Octane-safe).
--}}

{{-- Design tokens --}}
@includeIf('webkernel::panels.layout._tokens')
@includeIf('webkernel::panels.layout._typography')

{{-- Page shell --}}
@includeIf('webkernel::panels.layout._page')

{{-- Topbar --}}
@includeIf('webkernel::panels.layout.topbar._base')
@includeIf('webkernel::panels.layout.topbar._colors')

{{-- Sidebar --}}
@includeIf('webkernel::panels.layout.sidebar._base')
@includeIf('webkernel::panels.layout.sidebar._items')
@includeIf('webkernel::panels.layout.sidebar._desktop')

{{-- Main content area --}}
@includeIf('webkernel::panels.layout._main')

{{-- Global components --}}
@includeIf('webkernel::panels.layout._table')
@includeIf('webkernel::panels.layout._scrollbar')
@includeIf('webkernel::panels.layout._modal')

{{-- JS hooks --}}
@includeIf('webkernel::panels.layout._script')

<style>
/* ── Webkernel Touch — CSS ────────────────────────────────────────────────── */
:root {
    --webkernel-touch-size:        52px;
    --webkernel-touch-peek:        10px;   /* px visible when hidden on edge */
    --webkernel-touch-panel-w:     300px;
    --webkernel-touch-radius:      14px;
    --webkernel-touch-z:           9999;
    --webkernel-touch-bg:          rgba(255,255,255,0.95);
    --webkernel-touch-bg-dark:     rgba(20,22,28,0.96);
    --webkernel-touch-border:      rgba(0,0,0,0.10);
    --webkernel-touch-border-dark: rgba(255,255,255,0.12);
    --webkernel-touch-shadow:      0 8px 32px rgba(0,0,0,0.18),0 1.5px 4px rgba(0,0,0,0.10);
    --webkernel-touch-shadow-dark: 0 8px 32px rgba(0,0,0,0.55),0 1.5px 4px rgba(0,0,0,0.30);
    --webkernel-touch-accent:      #256ffc;
    --webkernel-touch-text:        #1a1a1a;
    --webkernel-touch-muted:       #6b7280;
    --webkernel-touch-text-dark:   #f0f0f0;
    --webkernel-touch-muted-dark:  #9ca3af;
    --webkernel-touch-hover:       rgba(0,0,0,0.05);
    --webkernel-touch-hover-dark:  rgba(255,255,255,0.07);
    --webkernel-touch-divider:     rgba(0,0,0,0.07);
    --webkernel-touch-divider-dark:rgba(255,255,255,0.07);
    --webkernel-touch-ease:        220ms cubic-bezier(0.4,0,0.2,1);
}
@media (prefers-color-scheme: dark) {
    :root {
        --webkernel-touch-bg:      var(--webkernel-touch-bg-dark);
        --webkernel-touch-border:  var(--webkernel-touch-border-dark);
        --webkernel-touch-shadow:  var(--webkernel-touch-shadow-dark);
        --webkernel-touch-text:    var(--webkernel-touch-text-dark);
        --webkernel-touch-muted:   var(--webkernel-touch-muted-dark);
        --webkernel-touch-hover:   var(--webkernel-touch-hover-dark);
        --webkernel-touch-divider: var(--webkernel-touch-divider-dark);
    }
}
.dark {
    --webkernel-touch-bg:      var(--webkernel-touch-bg-dark);
    --webkernel-touch-border:  var(--webkernel-touch-border-dark);
    --webkernel-touch-shadow:  var(--webkernel-touch-shadow-dark);
    --webkernel-touch-text:    var(--webkernel-touch-text-dark);
    --webkernel-touch-muted:   var(--webkernel-touch-muted-dark);
    --webkernel-touch-hover:   var(--webkernel-touch-hover-dark);
    --webkernel-touch-divider: var(--webkernel-touch-divider-dark);
}

/* ── root wrapper ─────────────────────────────────────────────────────────── */
#webkernel-touch-root {
    position: fixed;
    z-index: var(--webkernel-touch-z);
    touch-action: none;
    -webkit-user-select: none;
    user-select: none;
}

/* ── main button ──────────────────────────────────────────────────────────── */
#webkernel-touch-btn {
    width:  var(--webkernel-touch-size);
    height: var(--webkernel-touch-size);
    border-radius: 50%;
    background:  var(--webkernel-touch-bg);
    border:      1px solid var(--webkernel-touch-border);
    box-shadow:  var(--webkernel-touch-shadow);
    display:     flex;
    align-items: center;
    justify-content: center;
    cursor:  pointer;
    padding: 0;
    outline: none;
    position: relative;
    transition: transform var(--webkernel-touch-ease),
                box-shadow var(--webkernel-touch-ease),
                opacity    var(--webkernel-touch-ease);
    -webkit-tap-highlight-color: transparent;
}
#webkernel-touch-btn:hover {
    transform:  scale(1.07);
    box-shadow: var(--webkernel-touch-shadow), 0 0 0 4px rgba(37,111,252,0.13);
}
#webkernel-touch-btn:active { transform: scale(0.96); }
#webkernel-touch-btn svg    { width:30px; height:30px; display:block; }

/* idle / peek states */
#webkernel-touch-btn.webkernel-touch-idle {
    opacity: 0.55;
}
#webkernel-touch-btn.webkernel-touch-idle:hover {
    opacity: 1;
}

/* ── hidden-on-edge (peek) ────────────────────────────────────────────────── */
/*
   When the user drags the button all the way to a screen edge it becomes
   "peeked": only --webkernel-touch-peek px remain visible.
   The root gets a data-peek="left|right|top|bottom" attribute.
   Hovering over the peeked sliver reveals the full button.
*/
#webkernel-touch-root[data-peek="left"]  { transform: translateX(calc(-1 * (var(--webkernel-touch-size) - var(--webkernel-touch-peek)))); }
#webkernel-touch-root[data-peek="right"] { transform: translateX(calc(var(--webkernel-touch-size) - var(--webkernel-touch-peek))); }
#webkernel-touch-root[data-peek="top"]   { transform: translateY(calc(-1 * (var(--webkernel-touch-size) - var(--webkernel-touch-peek)))); }
#webkernel-touch-root[data-peek="bottom"]{ transform: translateY(calc(var(--webkernel-touch-size) - var(--webkernel-touch-peek))); }
#webkernel-touch-root[data-peek]:hover,
#webkernel-touch-root[data-peek]:focus-within { transform: none; }
#webkernel-touch-root[data-peek] { transition: transform 250ms cubic-bezier(0.4,0,0.2,1); }

/* ── panel ────────────────────────────────────────────────────────────────── */
#webkernel-touch-panel {
    position:       absolute;
    width:          var(--webkernel-touch-panel-w);
    background:     var(--webkernel-touch-bg);
    border:         1px solid var(--webkernel-touch-border);
    box-shadow:     var(--webkernel-touch-shadow);
    border-radius:  var(--webkernel-touch-radius);
    overflow:       hidden;
    display:        none;
    flex-direction: column;
    transform-origin: bottom center;
    animation:      webkernel-touch-panel-in 180ms cubic-bezier(0.34,1.56,0.64,1) forwards;
}
#webkernel-touch-panel.webkernel-touch-panel-open { display:flex; }
@keyframes webkernel-touch-panel-in {
    from { opacity:0; transform:scale(0.88) translateY(8px); }
    to   { opacity:1; transform:scale(1)    translateY(0);   }
}

/* header */
.webkernel-touch-header {
    padding: 14px 16px 10px;
    border-bottom: 1px solid var(--webkernel-touch-divider);
    display: flex;
    align-items: center;
    gap: 10px;
}
.webkernel-touch-header-logo  { width:22px; height:22px; flex-shrink:0; }
.webkernel-touch-header-title { font-size:13px; font-weight:600; color:var(--webkernel-touch-text); letter-spacing:0.01em; }
.webkernel-touch-header-sub   { font-size:11px; color:var(--webkernel-touch-muted); margin-left:auto; }

/* quick grid */
.webkernel-touch-quick-grid {
    display: grid;
    grid-template-columns: repeat(4,1fr);
    gap:     2px;
    padding: 8px 8px 10px;
}
.webkernel-touch-quick-btn {
    display:        flex;
    flex-direction: column;
    align-items:    center;
    gap:        5px;
    padding:    8px 4px;
    border-radius: 10px;
    cursor:     pointer;
    border:     none;
    background: none;
    color:      var(--webkernel-touch-text);
    font-size:  10px;
    font-weight:500;
    text-align: center;
    transition: background var(--webkernel-touch-ease);
    -webkit-tap-highlight-color: transparent;
}
.webkernel-touch-quick-btn:hover { background: var(--webkernel-touch-hover); }
.webkernel-touch-quick-btn svg   { width:20px; height:20px; color:var(--webkernel-touch-accent); }

/* tabs */
.webkernel-touch-tabs {
    display: flex;
    border-bottom: 1px solid var(--webkernel-touch-divider);
    padding: 0 8px;
    gap: 2px;
}
.webkernel-touch-tab {
    flex:1; padding:8px 4px;
    font-size:11px; font-weight:500;
    color:var(--webkernel-touch-muted);
    border:none; background:none; cursor:pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    transition: color var(--webkernel-touch-ease), border-color var(--webkernel-touch-ease);
    text-align:center;
    -webkit-tap-highlight-color: transparent;
}
.webkernel-touch-tab.webkernel-touch-tab-active {
    color: var(--webkernel-touch-accent);
    border-bottom-color: var(--webkernel-touch-accent);
}
.webkernel-touch-tab-content               { display:none; }
.webkernel-touch-tab-content.webkernel-touch-tab-active { display:block; }

/* scroll area */
.webkernel-touch-scroll {
    max-height: 320px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: var(--webkernel-touch-divider) transparent;
}
.webkernel-touch-scroll::-webkit-scrollbar       { width:4px; }
.webkernel-touch-scroll::-webkit-scrollbar-thumb { background:var(--webkernel-touch-divider); border-radius:4px; }

/* section */
.webkernel-touch-section       { display:flex; flex-direction:column; }
.webkernel-touch-section-label {
    font-size:10px; font-weight:600; letter-spacing:0.08em;
    text-transform:uppercase; color:var(--webkernel-touch-muted);
    padding:10px 16px 4px;
}

/* list item */
.webkernel-touch-item {
    display:flex; align-items:center; gap:10px;
    padding:9px 16px;
    cursor:pointer;
    color:var(--webkernel-touch-text);
    font-size:13.5px;
    border:none; background:none; width:100%;
    text-align:left; text-decoration:none;
    transition: background var(--webkernel-touch-ease);
    -webkit-tap-highlight-color: transparent;
}
.webkernel-touch-item:hover   { background:var(--webkernel-touch-hover); }
.webkernel-touch-item:active  { background:rgba(37,111,252,0.09); }
.webkernel-touch-item-icon    { width:16px; height:16px; flex-shrink:0; display:flex; align-items:center; justify-content:center; color:var(--webkernel-touch-muted); }
.webkernel-touch-item-label   { flex:1; font-weight:400; }
.webkernel-touch-item-badge   { font-size:10px; padding:1px 6px; border-radius:20px; background:rgba(37,111,252,0.12); color:var(--webkernel-touch-accent); font-weight:500; }
.webkernel-touch-item-star    { display:none; /* shown only when HasWebkernelTouch trait detected */ }

/* divider */
.webkernel-touch-divider { height:1px; background:var(--webkernel-touch-divider); margin:4px 0; }

/* footer */
.webkernel-touch-footer { border-top:1px solid var(--webkernel-touch-divider); padding:6px 8px; display:flex; gap:4px; }
.webkernel-touch-footer-btn {
    flex:1; display:flex; align-items:center; justify-content:center; gap:5px;
    padding:7px 8px; border-radius:8px; border:none; background:none;
    color:var(--webkernel-touch-muted); font-size:11px; font-weight:500;
    cursor:pointer;
    transition: background var(--webkernel-touch-ease), color var(--webkernel-touch-ease);
    -webkit-tap-highlight-color: transparent;
}
.webkernel-touch-footer-btn:hover { background:var(--webkernel-touch-hover); color:var(--webkernel-touch-text); }
.webkernel-touch-footer-btn svg   { width:14px; height:14px; }

/* ── standalone context menu ──────────────────────────────────────────────── */
#webkernel-touch-ctx {
    position:fixed; z-index:calc(var(--webkernel-touch-z) + 1);
    width:220px;
    background:var(--webkernel-touch-bg);
    border:1px solid var(--webkernel-touch-border);
    box-shadow:var(--webkernel-touch-shadow);
    border-radius:var(--webkernel-touch-radius);
    padding:6px 0;
    display:none; flex-direction:column;
    animation:webkernel-touch-panel-in 160ms cubic-bezier(0.34,1.56,0.64,1) forwards;
}
#webkernel-touch-ctx.webkernel-touch-ctx-open { display:flex; }
.webkernel-touch-ctx-item {
    display:flex; align-items:center; gap:10px;
    padding:8px 14px; cursor:pointer;
    color:var(--webkernel-touch-text); font-size:13px;
    border:none; background:none; width:100%;
    text-align:left; text-decoration:none;
    transition:background var(--webkernel-touch-ease);
    -webkit-tap-highlight-color:transparent;
}
.webkernel-touch-ctx-item:hover { background:var(--webkernel-touch-hover); }
.webkernel-touch-ctx-item-icon  { width:15px; height:15px; color:var(--webkernel-touch-muted); flex-shrink:0; }
.webkernel-touch-ctx-divider    { height:1px; background:var(--webkernel-touch-divider); margin:4px 12px; }
</style>
