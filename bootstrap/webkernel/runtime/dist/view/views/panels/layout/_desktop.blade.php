{{-- webkernel::panels.layout._desktop — desktop layout + sidebar (private partial) --}}
{{--
    1024px+: Filament's `lg:` utilities kick in, sidebar enters document flow.
    $sidebarKeepsBackground (bool):
      true  → icon-strip / collapsible-on-desktop: sidebar always visible, always needs bg.
      false → fully-collapsible: transparent when closed is correct.
--}}
<style>

@media (min-width: 1024px) {

    /* ── Body overflow lock ──────────────────────────────────────────────── */
    html,
    body {
        margin: 0;
        padding: 0;

        width: 100% !important;
        position: relative !important;
    }

    /* ── Context-aware content-offset overrides ──────────────────────────── */
    .fi-body:not(:has(.fi-topbar-ctn)) {
        --wds-content-offset: var(--wds-content-offset-without-topbar);
        --wds-sidebar-height: calc(100vh - var(--wds-content-offset-without-topbar));
    }
    .fi-body:not(:has(.fi-topbar-ctn)):has(.fi-layout-sidebar-toggle-btn-ctn) {
        --wds-content-offset: var(--wds-content-offset-with-toggle);
        --wds-sidebar-height: calc(100vh - var(--wds-content-offset-with-toggle));
    }

    /* ── Sidebar toggle container ────────────────────────────────────────── */
    .fi-layout-sidebar-toggle-btn-ctn {
        position: sticky !important;
        top: 0 !important;
        height: var(--wds-sidebar-toggle-height) !important;
        background-color: var(--wds-color-surface) !important;
        border-radius: var(--wds-radius-container) !important;
        box-shadow:
            0 var(--wds-shadow-y) var(--wds-shadow-blur) var(--wds-shadow-spread)
                rgba(0, 0, 0, var(--wds-shadow-opacity)),
            0 0 0 1px rgba(0, 0, 0, var(--wds-shadow-border-opacity)) !important;
        margin: var(--wds-space-top) var(--wds-space-outer) 0 !important;
        backdrop-filter: blur(var(--wds-backdrop-blur)) !important;
        overflow: visible !important;
    }
    .dark .fi-layout-sidebar-toggle-btn-ctn,
    .fi-layout-sidebar-toggle-btn-ctn:where(.dark, .dark *),
    [data-theme="dark"] .fi-layout-sidebar-toggle-btn-ctn {
        background-color: var(--wds-color-topbar-dark) !important;
        box-shadow:
            0 var(--wds-shadow-y) var(--wds-shadow-blur) var(--wds-shadow-spread)
                rgba(0, 0, 0, var(--wds-shadow-dark-opacity)),
            0 0 0 1px rgba(255, 255, 255, var(--wds-shadow-dark-border-opacity)) !important;
    }


    @if ($sidebarKeepsBackground)
    {{-- ── icon-strip / collapsible-on-desktop: bg always present ── --}}
    .fi-sidebar-item.fi-sidebar-item-has-url > .fi-sidebar-item-btn {
        border: 1px solid transparent;
        border-radius: 0.5rem;
        padding: 0.4rem 0.6rem;
        transition: border-color 0.15s ease;
    }
    .fi-sidebar-item.fi-sidebar-item-has-url > .fi-sidebar-item-btn:hover,
    .fi-sidebar-item.fi-active.fi-sidebar-item-has-url > .fi-sidebar-item-btn {
        border-color: color-mix(in oklab, currentColor 6%, transparent);
    }
    aside.fi-sidebar.fi-main-sidebar {
        background-color: var(--wds-color-surface) !important;
        box-shadow:
            0 var(--wds-shadow-y) var(--wds-shadow-blur) var(--wds-shadow-spread)
                rgba(0, 0, 0, var(--wds-shadow-opacity)),
            0 0 0 1px rgba(0, 0, 0, var(--wds-shadow-border-opacity)) !important;
        backdrop-filter: blur(var(--wds-backdrop-blur)) !important;
    }
    .dark aside.fi-sidebar.fi-main-sidebar,
    [data-theme="dark"] aside.fi-sidebar.fi-main-sidebar {
        background-color: var(--wds-color-surface-dark) !important;
        box-shadow:
            0 var(--wds-shadow-y) var(--wds-shadow-blur) var(--wds-shadow-spread)
                rgba(0, 0, 0, var(--wds-shadow-dark-opacity)),
            0 0 0 1px rgba(255, 255, 255, var(--wds-shadow-dark-border-opacity)) !important;
    }
    aside .fi-sidebar-item,
    aside .fi-sidebar-group-btn {
        margin-left:  var(--wds-sidebar-item-margin-left) !important;
        margin-right: calc(var(--wds-sidebar-item-margin-right) * 1.2) !important;
    }
    @else
    {{-- ── fully-collapsible: transparent/no shadow when closed ── --}}
    aside.fi-sidebar.fi-main-sidebar {
        background-color: transparent !important;
        box-shadow:       none !important;
        backdrop-filter:  none !important;
    }
    aside .fi-sidebar-item,
    aside .fi-sidebar-group-btn {
        margin-left:  var(--wds-sidebar-item-margin-left) !important;
        margin-right: var(--wds-sidebar-item-margin-right) !important;
    }
    @endif

    /* ── Sidebar open state (overrides closed state above) ──────────────── */
    aside.fi-sidebar.fi-main-sidebar.fi-sidebar-open {
        background-color: var(--wds-color-surface) !important;
        box-shadow:
            0 var(--wds-shadow-y) var(--wds-shadow-blur) var(--wds-shadow-spread)
                rgba(0, 0, 0, var(--wds-shadow-opacity)),
            0 0 0 1px rgba(0, 0, 0, var(--wds-shadow-border-opacity)) !important;
        backdrop-filter: blur(var(--wds-backdrop-blur)) !important;
    }
    .dark aside.fi-sidebar.fi-main-sidebar.fi-sidebar-open,
    [data-theme="dark"] aside.fi-sidebar.fi-main-sidebar.fi-sidebar-open {
        background-color: var(--wds-color-surface-dark) !important;
        box-shadow:
            0 var(--wds-shadow-y) var(--wds-shadow-blur) var(--wds-shadow-spread)
                rgba(0, 0, 0, var(--wds-shadow-dark-opacity)),
            0 0 0 1px rgba(255, 255, 255, var(--wds-shadow-dark-border-opacity)) !important;
    }

    /* ── Sidebar inner layout ────────────────────────────────────────────── */
    .fi-sidebar-nav {
        padding-left:          var(--wds-sidebar-padding-x) !important;
        padding-right:         var(--wds-sidebar-padding-x) !important;
        padding-inline:        0 !important;
        position:              relative !important;
        overflow-y:            auto !important;
        overflow-x:            auto !important;
        scrollbar-width:       none !important;
        -ms-overflow-style:    none !important;
    }
    .fi-sidebar-nav::-webkit-scrollbar { width: 0 !important; height: 0 !important; }
    .fi-sidebar-nav-groups  { row-gap: calc(var(--spacing) * 2.5); }
    .fi-sidebar-footer      { row-gap: calc(var(--spacing) * 2.5); }
    .fi-sidebar-group-label { font-weight: 400 !important; }
    aside .fi-sidebar-group-items .fi-sidebar-item.fi-active.fi-sidebar-item-has-url {
        margin-left:  0;
        margin-right: 0;
    }

    /* ── Fine scrollbars (all scrollable areas) ──────────────────────────── */
    *::-webkit-scrollbar        { width: var(--wds-scrollbar-size) !important; height: var(--wds-scrollbar-size) !important; }
    *::-webkit-scrollbar-track  { background: transparent !important; margin: 0 !important; }
    *::-webkit-scrollbar-thumb  {
        background:    rgba(0, 0, 0, var(--wds-scrollbar-opacity)) !important;
        border-radius: var(--wds-radius-container) !important;
    }
    *::-webkit-scrollbar-thumb:hover         { background: rgba(0, 0, 0, var(--wds-scrollbar-opacity-hover)) !important; }
    .dark *::-webkit-scrollbar-thumb         { background: rgba(255, 255, 255, var(--wds-scrollbar-opacity)) !important; }
    .dark *::-webkit-scrollbar-thumb:hover   { background: rgba(255, 255, 255, var(--wds-scrollbar-opacity-hover)) !important; }

}
</style>
