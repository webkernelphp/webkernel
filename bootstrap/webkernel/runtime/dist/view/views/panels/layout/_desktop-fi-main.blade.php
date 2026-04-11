@php
    $panel = function_exists('filament') ? filament()->getCurrentOrDefaultPanel() : null;
    $isCollapsible = $panel?->isSidebarCollapsibleOnDesktop();
    $isFully = $panel?->isSidebarFullyCollapsibleOnDesktop();
    $mode = $isCollapsible ? 'collapsible' : ($isFully ? 'fully' : null);
    $isTopNav = $panel?->topNavigation();
    $hasNav = !empty($panel?->getNavigation());
@endphp
<style>
@media (min-width: 1024px) {
    .fi-main-ctn .fi-main {
        height: calc(100vh - var(--wds-content-offset)) !important;
        max-height: calc(100vh - var(--wds-content-offset)) !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        position: fixed !important;
    }
    @if (!$hasNav)
        aside.fi-sidebar.fi-main-sidebar {
            display: none !important;
        }
    @elseif ($mode)
        .fi-main-ctn {
            overflow: hidden !important;
        }
        aside.fi-sidebar.fi-main-sidebar {
            transform: translateX(-100%);
            transition: none;
            will-change: transform;
        }
        :has(aside.fi-sidebar.fi-main-sidebar.fi-sidebar-open) aside.fi-sidebar.fi-main-sidebar {
            transform: translateX(0);
            transition: transform 0.2s ease-out;
        }
        .fi-main {
            width:
                @if ($mode === 'collapsible')
                    calc(100vw - calc(var(--sidebar-width)/5.2) - calc(var(--wds-space-top) * 3))
                @else
                    calc(100vw - calc(var(--wds-space-top) * 2))
                @endif
            !important;
        }
        :has(aside.fi-sidebar.fi-main-sidebar.fi-sidebar-open) .fi-main {
            width: calc(100vw - calc(var(--sidebar-width) * 1.1)) !important;
        }
    @endif
}
</style>
