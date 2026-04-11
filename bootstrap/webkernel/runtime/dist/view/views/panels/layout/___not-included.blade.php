{{--
From _tablet.blade.php

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

/* ── Main content container ──────────────────────────────────────────── */


.fi-main-ctn {
    width: 100% !important;
    padding-left: var(--wds-space-outer) !important;
    padding-right: var(--wds-space-outer) !important;
    position: relative !important;
    box-sizing: border-box !important;
}


/* ── Database notifications panel ────────────────────────────────────── */
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
_desktop.blade.php


/* ── Sidebar shell ───────────────────────────────────────────────────── */
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
