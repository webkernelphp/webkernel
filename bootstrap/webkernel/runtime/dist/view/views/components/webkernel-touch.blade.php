{{--
    Webkernel Touch
    ================
    Standalone floating action button — no Filament component class, no trait required.

    Variables expected (set via @php before @include, or via webkernel-touch-demo-mode):
      $wktPanels   array   [['label' => 'admin', 'url' => '/admin'], ...]   default []
      $wktUser     array   ['name' => '...', 'email' => '...']               default null
      $wktFavorites array  [['url' => '...', 'title' => '...'], ...]         default []
      $wktEnabled  bool    show/hide the component                           default true

    If $wktEnabled is false the component renders nothing.
    If HasWebkernelTouch trait is present on the auth user it will be used
    automatically — but nothing breaks if it is absent.
--}}
@php
    // ── resolve variables ────────────────────────────────────────────────────

    $wktEnabled = true;

    $wktPanels = [
        ['label' => 'admin',   'url' => '/admin'],
        ['label' => 'app',     'url' => '/app'],
        ['label' => 'horizon', 'url' => '/horizon'],
    ];

    $wktUser = [
        'name'  => 'Demo User',
        'email' => 'demo@webkernel.dev',
    ];

    /*
     * Pre-seeded favorites — will be written to localStorage on first load
     * so the Main tab is never empty in demo mode.
     */
    $wktFavorites = [
        ['url' => '/admin/users',    'title' => 'Users'],
        ['url' => '/admin/settings', 'title' => 'Settings'],
        ['url' => '/admin/logs',     'title' => 'Logs'],
    ];

    // Auto-detect trait on authenticated user (optional — no crash if absent)
    if ($wktEnabled && auth()->check()) {
        $authUser = auth()->user();
        if (method_exists($authUser, 'hasWebkernelTouchEnabled')) {
            $wktEnabled = $authUser->hasWebkernelTouchEnabled();
        }
        if ($wktEnabled && method_exists($authUser, 'getWebkernelTouchFavorites') && empty($wktFavorites)) {
            $wktFavorites = $authUser->getWebkernelTouchFavorites();
        }
        if ($wktUser === null) {
            $wktUser = [
                'name'  => $authUser->name  ?? $authUser->email ?? null,
                'email' => $authUser->email ?? null,
            ];
        }
    }

    $wktFavoritesJson = json_encode(array_values($wktFavorites), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $wktPanelsJson    = json_encode(array_values($wktPanels),    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp

@if($wktEnabled)

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



{{-- ── DATA passed to JS ──────────────────────────────────────────────────── --}}
<script>
window.wktPanels    = {!! $wktPanelsJson !!};
window.wktFavorites = {!! $wktFavoritesJson !!};
window.wktUser      = {!! json_encode($wktUser) !!};
window.wktHasTrait  = {{ (auth()->check() && method_exists(auth()->user(), 'getWebkernelTouchFavorites')) ? 'true' : 'false' }};
</script>

{{-- ── MARKUP ──────────────────────────────────────────────────────────────── --}}
<div id="webkernel-touch-root">

    {{-- Main floating button --}}
    <button id="webkernel-touch-btn" class="webkernel-touch-idle"
            title="Webkernel Touch" aria-label="Webkernel Touch menu">
        {{-- Webkernel logo inline — no external class needed --}}
        <svg width="30" height="30" viewBox="0 0 6.35 6.3497" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
            <defs>
                <linearGradient id="wkt-grad-b" x1="-15127" x2="-15037" y1="-7387.3" y2="-7387.3" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#002a6e" offset="0"/>
                    <stop stop-color="#002cd7" stop-opacity="0" offset="1"/>
                </linearGradient>
                <linearGradient id="wkt-grad-a" x1="-15127" x2="-15082" y1="-7406.4" y2="-7387.3" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#001f3e" offset="0"/>
                    <stop stop-color="#256ffc" offset="1"/>
                </linearGradient>
            </defs>
            <g transform="translate(-4.4775e-5 -2.4596e-5)">
                <g transform="matrix(.070551 0 0 .070551 1067.3 524.36)">
                    <g fill="url(#wkt-grad-b)">
                        <path d="m-15060-7387.3a22.959 22.959 0 0 1-22.96 22.959 22.959 22.959 0 0 1-22.959-22.959 22.959 22.959 0 0 1 22.959-22.959 22.959 22.959 0 0 1 22.96 22.959zm-22.702-34.005c-18.747-0.143-34.115 14.993-34.258 33.74-0.04 5.096 1.059 9.939 3.044 14.298 0.203-1.814 1.221-3.515 2.915-4.512 1.915-1.126 4.197-1.063 6.001-0.042-1.281-2.966-1.986-6.235-1.96-9.668 0.101-13.233 10.949-23.917 24.182-23.817s23.917 10.949 23.817 24.182c-0.07 9.503-5.687 17.691-13.747 21.515 0 0-0.02 0.01-0.03 0.013-3.157 1.493-6.689 2.318-10.409 2.289-0.305 0-0.608-0.016-0.91-0.03v0.01c-1.648-0.078-3.3-0.323-4.935-0.747-6.205-1.61-11.412-5.54-14.661-11.066-1.399-2.38-4.464-3.176-6.844-1.776-2.38 1.399-3.176 4.464-1.776 6.844 4.603 7.829 11.979 13.396 20.77 15.677 7.243 1.879 14.73 1.336 21.521-1.487 0.503-0.209 1.003-0.43 1.498-0.664 0.02-0.01 0.04-0.022 0.07-0.032 0.909-0.432 1.807-0.901 2.685-1.417 0.04-0.023 0.07-0.052 0.112-0.076 9.886-5.889 16.56-16.65 16.653-28.973 0.141-18.748-14.995-34.116-33.742-34.259zm44.423 28.652c-1.825-15.25-11.124-27.813-23.784-34.479 0.94 1.59 1.142 3.588 0.359 5.416-0.863 2.017-2.703 3.313-4.73 3.583 9.706 5.221 16.819 14.918 18.225 26.668 2.294 19.162-11.43 36.618-30.592 38.912s-36.618-11.43-38.912-30.592c-1.593-13.308 4.547-25.784 14.862-32.937 0.24-0.166 0.48-0.332 0.724-0.491 0.133-0.087 0.268-0.173 0.403-0.258 0.387-0.245 0.776-0.485 1.172-0.715 0.03-0.015 0.05-0.031 0.08-0.046 9.228-5.321 20.761-6.393 31.286-1.889 2.539 1.086 5.477-0.091 6.564-2.629 1.087-2.539-0.09-5.478-2.629-6.564-12.014-5.143-25.051-4.626-36.129 0.343 0.01-0.015 0.02-0.032 0.03-0.047-3.341 1.498-6.45 3.391-9.28 5.607-0.32 0.249-0.633 0.506-0.946 0.764-0.135 0.112-0.27 0.222-0.404 0.335-5.202 4.379-9.492 9.993-12.346 16.66-0.02 0.034-0.02 0.07-0.03 0.105-2.993 7.006-4.248 14.86-3.279 22.953 2.949 24.637 25.393 42.282 50.03 39.332s42.281-25.394 39.332-50.031z"
                              fill="url(#wkt-grad-a)" stop-color="#000000"/>
                    </g>
                </g>
            </g>
        </svg>
    </button>

    {{-- Panel --}}
    <div id="webkernel-touch-panel" role="dialog" aria-label="Webkernel Touch panel">

        {{-- Header --}}
        <div class="webkernel-touch-header">
            <div class="webkernel-touch-header-logo">
                <svg width="22" height="22" viewBox="0 0 6.35 6.3497" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <defs>
                        <linearGradient id="wkt-hdr-b" x1="-15127" x2="-15037" y1="-7387.3" y2="-7387.3" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#002a6e" offset="0"/>
                            <stop stop-color="#002cd7" stop-opacity="0" offset="1"/>
                        </linearGradient>
                        <linearGradient id="wkt-hdr-a" x1="-15127" x2="-15082" y1="-7406.4" y2="-7387.3" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#001f3e" offset="0"/>
                            <stop stop-color="#256ffc" offset="1"/>
                        </linearGradient>
                    </defs>
                    <g transform="translate(-4.4775e-5 -2.4596e-5)">
                        <g transform="matrix(.070551 0 0 .070551 1067.3 524.36)">
                            <g fill="url(#wkt-hdr-b)">
                                <path d="m-15060-7387.3a22.959 22.959 0 0 1-22.96 22.959 22.959 22.959 0 0 1-22.959-22.959 22.959 22.959 0 0 1 22.959-22.959 22.959 22.959 0 0 1 22.96 22.959zm-22.702-34.005c-18.747-0.143-34.115 14.993-34.258 33.74-0.04 5.096 1.059 9.939 3.044 14.298 0.203-1.814 1.221-3.515 2.915-4.512 1.915-1.126 4.197-1.063 6.001-0.042-1.281-2.966-1.986-6.235-1.96-9.668 0.101-13.233 10.949-23.917 24.182-23.817s23.917 10.949 23.817 24.182c-0.07 9.503-5.687 17.691-13.747 21.515 0 0-0.02 0.01-0.03 0.013-3.157 1.493-6.689 2.318-10.409 2.289-0.305 0-0.608-0.016-0.91-0.03v0.01c-1.648-0.078-3.3-0.323-4.935-0.747-6.205-1.61-11.412-5.54-14.661-11.066-1.399-2.38-4.464-3.176-6.844-1.776-2.38 1.399-3.176 4.464-1.776 6.844 4.603 7.829 11.979 13.396 20.77 15.677 7.243 1.879 14.73 1.336 21.521-1.487 0.503-0.209 1.003-0.43 1.498-0.664 0.02-0.01 0.04-0.022 0.07-0.032 0.909-0.432 1.807-0.901 2.685-1.417 0.04-0.023 0.07-0.052 0.112-0.076 9.886-5.889 16.56-16.65 16.653-28.973 0.141-18.748-14.995-34.116-33.742-34.259zm44.423 28.652c-1.825-15.25-11.124-27.813-23.784-34.479 0.94 1.59 1.142 3.588 0.359 5.416-0.863 2.017-2.703 3.313-4.73 3.583 9.706 5.221 16.819 14.918 18.225 26.668 2.294 19.162-11.43 36.618-30.592 38.912s-36.618-11.43-38.912-30.592c-1.593-13.308 4.547-25.784 14.862-32.937 0.24-0.166 0.48-0.332 0.724-0.491 0.133-0.087 0.268-0.173 0.403-0.258 0.387-0.245 0.776-0.485 1.172-0.715 0.03-0.015 0.05-0.031 0.08-0.046 9.228-5.321 20.761-6.393 31.286-1.889 2.539 1.086 5.477-0.091 6.564-2.629 1.087-2.539-0.09-5.478-2.629-6.564-12.014-5.143-25.051-4.626-36.129 0.343 0.01-0.015 0.02-0.032 0.03-0.047-3.341 1.498-6.45 3.391-9.28 5.607-0.32 0.249-0.633 0.506-0.946 0.764-0.135 0.112-0.27 0.222-0.404 0.335-5.202 4.379-9.492 9.993-12.346 16.66-0.02 0.034-0.02 0.07-0.03 0.105-2.993 7.006-4.248 14.86-3.279 22.953 2.949 24.637 25.393 42.282 50.03 39.332s42.281-25.394 39.332-50.031z"
                                      fill="url(#wkt-hdr-a)" stop-color="#000000"/>
                            </g>
                        </g>
                    </g>
                </svg>
            </div>
            <span class="webkernel-touch-header-title">Webkernel Touch</span>
            <span class="webkernel-touch-header-sub" id="webkernel-touch-page-info"></span>
        </div>

        {{-- Quick grid --}}
        <div class="webkernel-touch-quick-grid">
            <button class="webkernel-touch-quick-btn" onclick="window.history.back()" title="Back">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                Back
            </button>
            <button class="webkernel-touch-quick-btn" onclick="window.history.forward()" title="Forward">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                Forward
            </button>
            <button class="webkernel-touch-quick-btn" onclick="window.location.reload()" title="Refresh">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                Refresh
            </button>
            <button class="webkernel-touch-quick-btn" onclick="window.scrollTo({top:0,behavior:'smooth'})" title="Top">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
                Top
            </button>
        </div>

        {{-- Tabs --}}
        <div class="webkernel-touch-tabs">
            <button class="webkernel-touch-tab webkernel-touch-tab-active" data-wkt-tab="webkernel-touch-tab-main">Main</button>
            <button class="webkernel-touch-tab" data-wkt-tab="webkernel-touch-tab-context">Context</button>
            <button class="webkernel-touch-tab" data-wkt-tab="webkernel-touch-tab-panels" id="webkernel-touch-tab-panels-btn" style="display:none">Panels</button>
        </div>

        <div class="webkernel-touch-scroll">

            {{-- TAB: Main --}}
            <div class="webkernel-touch-tab-content webkernel-touch-tab-active" id="webkernel-touch-tab-main">
                <div class="webkernel-touch-section">
                    <div class="webkernel-touch-section-label">Favorites</div>
                    <div id="webkernel-touch-favorites-list">
                        <div class="webkernel-touch-item" style="color:var(--webkernel-touch-muted);font-size:12px;padding:8px 16px;">
                            No favorites yet.
                        </div>
                    </div>
                </div>
                <div class="webkernel-touch-divider"></div>
                <div class="webkernel-touch-section">
                    <div class="webkernel-touch-section-label">Page</div>
                    <div id="webkernel-touch-page-actions">
                        {{-- JS injects extra items here if needed --}}
                    </div>
                </div>
            </div>

            {{-- TAB: Context --}}
            <div class="webkernel-touch-tab-content" id="webkernel-touch-tab-context">
                <div class="webkernel-touch-section">
                    <div class="webkernel-touch-section-label">Navigation</div>
                    <button class="webkernel-touch-item" onclick="window.history.back()">
                        <span class="webkernel-touch-item-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><polyline points="15 18 9 12 15 6"/></svg>
                        </span>
                        <span class="webkernel-touch-item-label">Go back</span>
                    </button>
                    <button class="webkernel-touch-item" onclick="window.history.forward()">
                        <span class="webkernel-touch-item-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><polyline points="9 18 15 12 9 6"/></svg>
                        </span>
                        <span class="webkernel-touch-item-label">Go forward</span>
                    </button>
                    <button class="webkernel-touch-item" onclick="window.location.reload()">
                        <span class="webkernel-touch-item-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                        </span>
                        <span class="webkernel-touch-item-label">Refresh page</span>
                    </button>
                    <div class="webkernel-touch-divider"></div>
                    <button class="webkernel-touch-item" onclick="navigator.clipboard&&navigator.clipboard.writeText(window.location.href)">
                        <span class="webkernel-touch-item-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        </span>
                        <span class="webkernel-touch-item-label">Copy URL</span>
                    </button>
                    <div class="webkernel-touch-divider"></div>
                    <div class="webkernel-touch-section-label">Favorites</div>
                    <button class="webkernel-touch-item" id="webkernel-touch-add-fav">
                        <span class="webkernel-touch-item-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        </span>
                        <span class="webkernel-touch-item-label">Add this page</span>
                    </button>
                </div>
            </div>

            {{-- TAB: Panels --}}
            <div class="webkernel-touch-tab-content" id="webkernel-touch-tab-panels">
                <div class="webkernel-touch-section">
                    <div class="webkernel-touch-section-label">Switch panel</div>
                    <div id="webkernel-touch-panels-list"></div>
                </div>
            </div>

        </div>{{-- /.webkernel-touch-scroll --}}

        {{-- Footer --}}
        <div class="webkernel-touch-footer">
            <button class="webkernel-touch-footer-btn" id="webkernel-touch-open-ctx-btn" title="Open context menu">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
                Context
            </button>
            <button class="webkernel-touch-footer-btn" id="webkernel-touch-close-btn" title="Close">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                Close
            </button>
        </div>

    </div>{{-- /#webkernel-touch-panel --}}
</div>{{-- /#webkernel-touch-root --}}

{{-- Standalone context menu --}}
<div id="webkernel-touch-ctx" role="menu" aria-label="Webkernel context menu">
    <button class="webkernel-touch-ctx-item" onclick="window.history.back()">
        <span class="webkernel-touch-ctx-item-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px"><polyline points="15 18 9 12 15 6"/></svg>
        </span>
        Go back
    </button>
    <button class="webkernel-touch-ctx-item" onclick="window.history.forward()">
        <span class="webkernel-touch-ctx-item-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px"><polyline points="9 18 15 12 9 6"/></svg>
        </span>
        Go forward
    </button>
    <button class="webkernel-touch-ctx-item" onclick="window.location.reload()">
        <span class="webkernel-touch-ctx-item-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
        </span>
        Refresh
    </button>
    <div class="webkernel-touch-ctx-divider"></div>
    <button class="webkernel-touch-ctx-item" onclick="navigator.clipboard&&navigator.clipboard.writeText(window.location.href)">
        <span class="webkernel-touch-ctx-item-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
        </span>
        Copy URL
    </button>
    <div class="webkernel-touch-ctx-divider"></div>
    <button class="webkernel-touch-ctx-item" id="webkernel-touch-ctx-open-panel">
        <span class="webkernel-touch-ctx-item-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        </span>
        Open Webkernel Touch
    </button>
</div>

@once
<script>
(function () {
    'use strict';

    /* ── constants ─────────────────────────────────────────────────────────── */
    var LS_POS  = 'webkernel_touch_pos';
    var LS_FAVS = 'webkernel_touch_favs';
    var SNAP    = 12;   /* px from screen edge for snap */
    var PEEK    = 10;   /* px visible when peeked */
    var IDLE_MS = 2800;
    /* Peek threshold: if button center is within this many px of an edge, peek */
    var PEEK_THRESHOLD = 30;

    /* ── DOM refs ──────────────────────────────────────────────────────────── */
    var root     = document.getElementById('webkernel-touch-root');
    var btn      = document.getElementById('webkernel-touch-btn');
    var panel    = document.getElementById('webkernel-touch-panel');
    var ctx      = document.getElementById('webkernel-touch-ctx');
    var pageInfo = document.getElementById('webkernel-touch-page-info');

    /* ── state ─────────────────────────────────────────────────────────────── */
    var panelOpen  = false;
    var ctxOpen    = false;
    var isDragging = false;
    var dragOffX   = 0, dragOffY = 0;
    var dragMoved  = false;
    var idleTimer  = null;

    /* ── helpers ───────────────────────────────────────────────────────────── */
    function clamp(v, lo, hi) { return Math.min(Math.max(v, lo), hi); }

    function savePos(x, y) {
        try { localStorage.setItem(LS_POS, JSON.stringify({x:x, y:y})); } catch(e){}
    }
    function loadPos() {
        try { var r = localStorage.getItem(LS_POS); return r ? JSON.parse(r) : null; } catch(e){ return null; }
    }
    function applyPos(x, y) {
        root.style.left = x + 'px';
        root.style.top  = y + 'px';
    }

    /* Snap to nearest edge after drag release */
    function snapToEdge(x, y) {
        var bw = window.innerWidth, bh = window.innerHeight;
        var sz = 52;
        var cx = x + sz/2, cy = y + sz/2;
        var dl = cx, dr = bw-cx, dt = cy, db = bh-cy;
        var mn = Math.min(dl, dr, dt, db);
        if      (mn === dl) x = SNAP;
        else if (mn === dr) x = bw - sz - SNAP;
        else if (mn === dt) y = SNAP;
        else                y = bh - sz - SNAP;
        return { x: clamp(x, SNAP, bw-sz-SNAP), y: clamp(y, SNAP, bh-sz-SNAP) };
    }

    /* Peek: if the button is very close to an edge, slide it almost off-screen */
    function updatePeek() {
        var x  = parseFloat(root.style.left)  || 0;
        var y  = parseFloat(root.style.top)   || 0;
        var bw = window.innerWidth;
        var bh = window.innerHeight;
        var sz = 52;
        if (panelOpen || ctxOpen) { root.removeAttribute('data-peek'); return; }
        if      (x <= PEEK_THRESHOLD)          root.setAttribute('data-peek', 'left');
        else if (x >= bw - sz - PEEK_THRESHOLD) root.setAttribute('data-peek', 'right');
        else if (y <= PEEK_THRESHOLD)           root.setAttribute('data-peek', 'top');
        else if (y >= bh - sz - PEEK_THRESHOLD) root.setAttribute('data-peek', 'bottom');
        else                                    root.removeAttribute('data-peek');
    }

    function initPos() {
        var saved = loadPos();
        var bw = window.innerWidth, bh = window.innerHeight, sz = 52;
        var x, y;
        if (saved) {
            x = clamp(saved.x, SNAP, bw-sz-SNAP);
            y = clamp(saved.y, SNAP, bh-sz-SNAP);
        } else {
            x = bw - sz - SNAP;
            y = bh - sz - 80;
        }
        applyPos(x, y);
        updatePeek();
    }

    function setIdle() {
        clearTimeout(idleTimer);
        btn.classList.remove('webkernel-touch-idle');
        idleTimer = setTimeout(function () {
            if (!panelOpen && !ctxOpen) {
                btn.classList.add('webkernel-touch-idle');
                updatePeek();
            }
        }, IDLE_MS);
    }

    /* ── panel position (avoids off-screen) ───────────────────────────────── */
    function posPanel() {
        var bw = window.innerWidth, bh = window.innerHeight;
        var rx = parseFloat(root.style.left)||0;
        var ry = parseFloat(root.style.top)||0;
        var pw = 300, ph = panel.offsetHeight || 420, sz = 52;
        var lx = rx - pw - 8;
        if (lx < 8) lx = rx + sz + 8;
        if (lx + pw > bw - 8) lx = Math.max(8, bw - pw - 8);
        var ly = ry;
        if (ly + ph > bh - 8) ly = Math.max(8, bh - ph - 8);
        panel.style.left = (lx - rx) + 'px';
        panel.style.top  = (ly - ry) + 'px';
    }

    /* ── panel open/close ──────────────────────────────────────────────────── */
    function openPanel() {
        panelOpen = true;
        panel.classList.add('webkernel-touch-panel-open');
        root.removeAttribute('data-peek');
        setTimeout(posPanel, 0);
        btn.classList.remove('webkernel-touch-idle');
        closeCtx();
        renderFavorites();
        renderPanels();
        if (pageInfo) pageInfo.textContent = (document.title||'').substring(0,22);
    }
    function closePanel() {
        panelOpen = false;
        panel.classList.remove('webkernel-touch-panel-open');
        setIdle();
    }

    /* ── context menu ──────────────────────────────────────────────────────── */
    function posCtx(x, y) {
        var bw = window.innerWidth, bh = window.innerHeight;
        var mw = 220, mh = ctx.offsetHeight || 220;
        var lx = (x + mw > bw - 8) ? x - mw : x;
        var ly = (y + mh > bh - 8) ? y - mh : y;
        ctx.style.left = Math.max(8, lx) + 'px';
        ctx.style.top  = Math.max(8, ly) + 'px';
    }
    function openCtx(x, y) {
        ctxOpen = true;
        ctx.classList.add('webkernel-touch-ctx-open');
        setTimeout(function(){ posCtx(x, y); }, 0);
        closePanel();
    }
    function closeCtx() {
        ctxOpen = false;
        ctx.classList.remove('webkernel-touch-ctx-open');
    }

    /* ── drag — mouse ──────────────────────────────────────────────────────── */
    btn.addEventListener('mousedown', function(e) {
        if (e.button !== 0) return;
        isDragging = true; dragMoved = false;
        var r = root.getBoundingClientRect();
        dragOffX = e.clientX - r.left;
        dragOffY = e.clientY - r.top;
        document.body.style.userSelect = 'none';
    });
    document.addEventListener('mousemove', function(e) {
        if (!isDragging) return;
        var nx = clamp(e.clientX - dragOffX, 0, window.innerWidth  - 52);
        var ny = clamp(e.clientY - dragOffY, 0, window.innerHeight - 52);
        applyPos(nx, ny);
        if (panelOpen) posPanel();
        dragMoved = true;
    });
    document.addEventListener('mouseup', function() {
        if (!isDragging) return;
        isDragging = false;
        document.body.style.userSelect = '';
        if (dragMoved) {
            var s = snapToEdge(parseFloat(root.style.left), parseFloat(root.style.top));
            root.style.transition = 'left 200ms ease,top 200ms ease';
            applyPos(s.x, s.y);
            savePos(s.x, s.y);
            if (panelOpen) setTimeout(posPanel, 210);
            setTimeout(function(){ root.style.transition=''; updatePeek(); }, 220);
        } else {
            if (panelOpen) closePanel(); else openPanel();
        }
        setIdle();
    });

    /* ── drag — touch ──────────────────────────────────────────────────────── */
    btn.addEventListener('touchstart', function(e) {
        if (e.touches.length !== 1) return;
        var t = e.touches[0];
        isDragging = true; dragMoved = false;
        var r = root.getBoundingClientRect();
        dragOffX = t.clientX - r.left;
        dragOffY = t.clientY - r.top;
        e.preventDefault();
    }, {passive:false});
    btn.addEventListener('touchmove', function(e) {
        if (!isDragging) return;
        var t = e.touches[0];
        var nx = clamp(t.clientX - dragOffX, 0, window.innerWidth  - 52);
        var ny = clamp(t.clientY - dragOffY, 0, window.innerHeight - 52);
        applyPos(nx, ny);
        if (panelOpen) posPanel();
        dragMoved = true;
        e.preventDefault();
    }, {passive:false});
    btn.addEventListener('touchend', function(e) {
        if (!isDragging) return;
        isDragging = false;
        if (dragMoved) {
            var s = snapToEdge(parseFloat(root.style.left), parseFloat(root.style.top));
            root.style.transition = 'left 200ms ease,top 200ms ease';
            applyPos(s.x, s.y);
            savePos(s.x, s.y);
            if (panelOpen) setTimeout(posPanel, 210);
            setTimeout(function(){ root.style.transition=''; updatePeek(); }, 220);
        } else {
            if (panelOpen) closePanel(); else openPanel();
        }
        setIdle();
        e.preventDefault();
    }, {passive:false});

    /* ── right-click on .fi-main (Filament) or body ────────────────────────── */
    var ctxTarget = document.querySelector('.fi-main') || document.body;
    ctxTarget.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        openCtx(e.clientX, e.clientY);
    });

    /* ── outside-click dismissal ───────────────────────────────────────────── */
    document.addEventListener('click', function(e) {
        if (ctxOpen && !ctx.contains(e.target)) closeCtx();
        if (panelOpen && !panel.contains(e.target) && !btn.contains(e.target)) closePanel();
    });

    window.addEventListener('resize', function() {
        if (panelOpen) posPanel();
        if (ctxOpen)   closeCtx();
        initPos();
    });

    /* ── tabs ──────────────────────────────────────────────────────────────── */
    document.querySelectorAll('.webkernel-touch-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.webkernel-touch-tab').forEach(function(t){ t.classList.remove('webkernel-touch-tab-active'); });
            tab.classList.add('webkernel-touch-tab-active');
            var id = tab.getAttribute('data-wkt-tab');
            document.querySelectorAll('.webkernel-touch-tab-content').forEach(function(c){ c.classList.remove('webkernel-touch-tab-active'); });
            var tc = document.getElementById(id);
            if (tc) tc.classList.add('webkernel-touch-tab-active');
        });
    });

    /* ── favorites (localStorage — position is NOT stored in DB) ──────────── */
    function getFavs() {
        /* Seed with server-side favorites if localStorage is empty */
        try {
            var raw = localStorage.getItem(LS_FAVS);
            if (raw) return JSON.parse(raw);
            /* First visit: use server-side data from window.wktFavorites */
            if (window.wktFavorites && window.wktFavorites.length) {
                localStorage.setItem(LS_FAVS, JSON.stringify(window.wktFavorites));
                return window.wktFavorites;
            }
            return [];
        } catch(e){ return []; }
    }
    function saveFavs(favs) {
        try { localStorage.setItem(LS_FAVS, JSON.stringify(favs)); } catch(e){}
    }

    var STAR_SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';
    var GRID_SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>';

    function renderFavorites() {
        var list = document.getElementById('webkernel-touch-favorites-list');
        if (!list) return;
        var favs = getFavs();
        if (!favs.length) {
            list.innerHTML = '<div class="webkernel-touch-item" style="color:var(--webkernel-touch-muted);font-size:12px;padding:8px 16px;">No favorites yet.</div>';
            return;
        }
        list.innerHTML = '';
        favs.forEach(function(fav, idx) {
            var a = document.createElement('a');
            a.className = 'webkernel-touch-item';
            a.href = fav.url;
            /* Star remove-button: always visible (localStorage based) */
            a.innerHTML =
                '<span class="webkernel-touch-item-icon">' + STAR_SVG + '</span>' +
                '<span class="webkernel-touch-item-label">' + ((fav.title||fav.url)+'').substring(0,30) + '</span>' +
                '<button class="webkernel-touch-item-badge" data-rm="'+idx+'" style="cursor:pointer;background:rgba(239,68,68,0.12);color:#dc2626;" title="Remove">✕</button>';
            list.appendChild(a);
        });
        list.querySelectorAll('[data-rm]').forEach(function(b) {
            b.addEventListener('click', function(e) {
                e.preventDefault(); e.stopPropagation();
                var f = getFavs();
                f.splice(parseInt(b.getAttribute('data-rm')), 1);
                saveFavs(f);
                renderFavorites();
            });
        });
    }

    var addFavBtn = document.getElementById('webkernel-touch-add-fav');
    if (addFavBtn) {
        addFavBtn.addEventListener('click', function() {
            var favs = getFavs();
            var url  = window.location.href;
            if (!favs.find(function(f){ return f.url === url; })) {
                favs.push({url:url, title:document.title||url});
                saveFavs(favs);
            }
            renderFavorites();
            var mt = document.querySelector('[data-wkt-tab="webkernel-touch-tab-main"]');
            if (mt) mt.click();
        });
    }

    /* ── panels ────────────────────────────────────────────────────────────── */
    function renderPanels() {
        var list   = document.getElementById('webkernel-touch-panels-list');
        var tabBtn = document.getElementById('webkernel-touch-tab-panels-btn');
        if (!list) return;
        var panels = window.wktPanels || [];
        if (panels.length <= 1) {
            if (tabBtn) tabBtn.style.display = 'none';
            list.innerHTML = '';
            return;
        }
        if (tabBtn) tabBtn.style.display = '';
        list.innerHTML = '';
        panels.forEach(function(p) {
            var a = document.createElement('a');
            a.className = 'webkernel-touch-item';
            a.href = p.url || '#';
            a.innerHTML =
                '<span class="webkernel-touch-item-icon">' + GRID_SVG + '</span>' +
                '<span class="webkernel-touch-item-label">' + (p.label||p.url) + '</span>';
            list.appendChild(a);
        });
    }

    /* ── footer buttons ────────────────────────────────────────────────────── */
    var closeBtn = document.getElementById('webkernel-touch-close-btn');
    if (closeBtn) closeBtn.addEventListener('click', closePanel);

    var openCtxBtn = document.getElementById('webkernel-touch-open-ctx-btn');
    if (openCtxBtn) openCtxBtn.addEventListener('click', function() {
        closePanel();
        var r = btn.getBoundingClientRect();
        openCtx(r.left, r.top);
    });

    var ctxOpenPanel = document.getElementById('webkernel-touch-ctx-open-panel');
    if (ctxOpenPanel) ctxOpenPanel.addEventListener('click', function(){ closeCtx(); openPanel(); });

    /* ── init ──────────────────────────────────────────────────────────────── */
    function init() {
        initPos();
        setIdle();
        renderFavorites();
        renderPanels();
    }
    document.addEventListener('livewire:navigated', init);
    init();
})();
</script>
@endonce
