{{--
    webkernel::panels.layout._not-included
    ───────────────────────────────────────
    Graveyard / archive — never included by css.blade.php.
    Snippets kept here for reference or future revival.
--}}

{{--
    From _tablet.blade.php — floating topbar (replaced by flat surface color)

    .fi-topbar {
        background-color: var(--wds-color-surface) !important;
        border-radius: var(--wds-radius-container) !important;
        box-shadow:
            0 var(--wds-shadow-y) var(--wds-shadow-blur) var(--wds-shadow-spread)
                rgba(0, 0, 0, var(--wds-shadow-opacity)),
            0 0 0 1px rgba(0, 0, 0, var(--wds-shadow-border-opacity)) !important;
        margin: var(--wds-space-top) var(--wds-space-outer) 0 !important;
        height: var(--wds-topbar-height) !important;
        backdrop-filter: blur(var(--wds-backdrop-blur)) !important;
        overflow: visible !important;
    }
--}}

{{--
    From _tablet.blade.php — fi-main-ctn padding

    .fi-main-ctn {
        width: 100% !important;
        padding-left: var(--wds-space-outer) !important;
        padding-right: var(--wds-space-outer) !important;
        position: relative !important;
        box-sizing: border-box !important;
    }
--}}

{{--
    From _tablet.blade.php — database notifications panel

    #database-notifications > div:nth-child(2) > div {
        height: calc(
            100vh
            - var(--wds-content-offset)
            + var(--wds-space-outer)
            + 2 * var(--wds-topbar-height)
        ) !important;
        background-color: var(--wds-color-surface) !important;
        border-radius: var(--wds-radius-container) !important;
    }
--}}

{{--
    From _desktop.blade.php — floating sidebar shell (replaced by Filament default)

    aside.fi-sidebar.fi-main-sidebar {
        height: var(--wds-sidebar-height) !important;
        max-height: var(--wds-sidebar-height) !important;
        margin-left: var(--wds-space-outer) !important;
        margin-top: var(--wds-space-top) !important;
        margin-bottom: var(--wds-space-bottom) !important;
        padding: 0 !important;
        box-sizing: border-box !important;
        border-radius: var(--wds-radius-container) !important;
        transition:
            background-color 0.2s ease,
            box-shadow       0.2s ease,
            backdrop-filter  0.2s ease !important;
    }
--}}

{{--
    From _desktop.blade.php — sidebar toggle button container (floating pill)

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
    .dark .fi-layout-sidebar-toggle-btn-ctn {
        background-color: var(--wds-color-topbar-dark) !important;
        box-shadow:
            0 var(--wds-shadow-y) var(--wds-shadow-blur) var(--wds-shadow-spread)
                rgba(0, 0, 0, var(--wds-shadow-dark-opacity)),
            0 0 0 1px rgba(255, 255, 255, var(--wds-shadow-dark-border-opacity)) !important;
    }
--}}

{{--
    From _desktop-fi-main.blade.php — collapsible sidebar translate + fi-main width

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
        width: calc(100vw - calc(var(--wds-space-top) * 2)) !important;
    }
    :has(aside.fi-sidebar.fi-main-sidebar.fi-sidebar-open) .fi-main {
        width: calc(100vw - calc(var(--sidebar-width) * 1.1)) !important;
    }
--}}
