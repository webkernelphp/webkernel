{{-- webkernel::panels.layout.sidebar._desktop — open/closed state, context-aware offset, 1024px+ (private partial) --}}
<style>
@media (min-width: 1024px) {
    /* ── Sidebar open state ── */
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

    /* ── Sidebar closed state (transparent, no shadow) ── */
    aside.fi-sidebar.fi-main-sidebar:not(.fi-sidebar-open) {
        background-color: transparent !important;
        box-shadow:       none !important;
        backdrop-filter:  none !important;
    }

    /* ── Fine scrollbars (all scrollable areas on desktop) ── */
    *::-webkit-scrollbar        { width: var(--wds-scrollbar-size) !important; height: var(--wds-scrollbar-size) !important; }
    *::-webkit-scrollbar-track  { background: transparent !important; margin: 0 !important; }
    *::-webkit-scrollbar-thumb  {
        background:    rgba(0, 0, 0, var(--wds-scrollbar-opacity)) !important;
        border-radius: var(--wds-radius-container) !important;
    }
    *::-webkit-scrollbar-thumb:hover       { background: rgba(0, 0, 0, var(--wds-scrollbar-opacity-hover)) !important; }
    .dark *::-webkit-scrollbar-thumb       { background: rgba(255, 255, 255, var(--wds-scrollbar-opacity)) !important; }
    .dark *::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, var(--wds-scrollbar-opacity-hover)) !important; }
}
</style>
