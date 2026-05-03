{{-- webkernel::panels.layout.sidebar._desktop — open/closed state, context-aware offset, 1024px+ (private partial) --}}

<style>
@media (min-width: 1024px) {


    /* ── Sidebar open state ──
    .dark .fi-sidebar.fi-main-sidebar.fi-sidebar-open,
    [data-theme="dark"] .fi-sidebar.fi-main-sidebar.fi-sidebar-open {
        background-color: var(--wds-color-surface-dark) !important;
    }
    */

    /* ── Fine scrollbars (all scrollable areas on desktop) ── */
    *::-webkit-scrollbar        { width: var(--wds-scrollbar-size) !important; height: var(--wds-scrollbar-size) !important; }
    *::-webkit-scrollbar-track  { background: transparent !important; margin: 0 !important; }
    *::-webkit-scrollbar-thumb  {
        background:    rgba(0, 0, 0, var(--wds-scrollbar-opacity)) !important;
        border-radius: var(--wds-radius-container) !important;
    }
    *::-webkit-scrollbar-thumb:hover       { background: rgba(0, 0, 0, var(--wds-scrollbar-opacity-hover)) !important; }
    .dark *::-webkit-scrollbar-thumb       { background: rgba(255, 255, 255, var(--wds-scrollbar-opacity)) !important; }
}
</style>
