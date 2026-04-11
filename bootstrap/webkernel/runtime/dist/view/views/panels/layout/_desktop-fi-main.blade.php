@php
    $panel = function_exists('filament') ? filament()->getCurrentOrDefaultPanel() : null;

    $isCollapsible = $panel?->isSidebarCollapsibleOnDesktop();
    $isFully = $panel?->isSidebarFullyCollapsibleOnDesktop();

    // Force behavior: if both are true → treat as collapsible
    $mode = $isCollapsible ? 'collapsible' : ($isFully ? 'fully' : null);

    $isTopNav = $panel?->topNavigation();
@endphp

<style>
@media (min-width: 1024px) {
    /* ── Main panel: fixed height, scrolls internally ────────────────────── */
    .fi-main-ctn .fi-main {
        height: calc(100vh - var(--wds-content-offset)) !important;
        max-height: calc(100vh - var(--wds-content-offset)) !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        position: fixed !important;
    }

    @if ($isTopNav)
        /* Hide sidebar when topNavigation is enabled */
        aside.fi-sidebar.fi-main-sidebar {
            visibility: hidden;
        }

        .fi-main {
            width:
                    calc(100vw - calc(var(--wds-space-top) * 2))
            !important;
        }
    @endif

    @if ($mode)
        /* Sidebar CLOSED */
        .fi-main {
            width:
                @if ($mode === 'collapsible')
                    calc(100vw - calc(var(--sidebar-width)/5.2) - calc(var(--wds-space-top) * 3))
                @else
                    calc(100vw - calc(var(--wds-space-top) * 2))
                @endif
            !important;
        }

        /* Sidebar OPEN */
        :has(aside.fi-sidebar.fi-main-sidebar.fi-sidebar-open) .fi-main {
            width: calc(100vw - calc(var(--sidebar-width) * 1.1)) !important;
        }
    @endif
}
</style>
