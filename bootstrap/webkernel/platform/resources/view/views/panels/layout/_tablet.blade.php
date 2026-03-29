{{-- webkernel::panels.layout._tablet — visual/aesthetic rules for 768px+ (private partial) --}}
{{-- Nothing here touches the sidebar — that lives in _desktop. --}}
<style>
@media (min-width: 768px) {

    /* ── Page background ─────────────────────────────────────────────────── */
    .fi-body {
        background-color: var(--wds-color-background) !important;
        position: relative;
    }
    .fi-body:where(.dark, .dark *),
    [data-theme="dark"] .fi-body {
        background-color: var(--wds-color-background-dark) !important;
    }

    /* ── Topbar ──────────────────────────────────────────────────────────── */
    .fi-topbar-ctn {
        position: sticky !important;
        top: 0 !important;
        z-index: 20 !important;
    }
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
    .dark .fi-topbar,
    .fi-topbar:where(.dark, .dark *),
    [data-theme="dark"] .fi-topbar {
        background-color: var(--wds-color-topbar-dark) !important;
        box-shadow:
            0 var(--wds-shadow-y) var(--wds-shadow-blur) var(--wds-shadow-spread)
                rgba(0, 0, 0, var(--wds-shadow-dark-opacity)),
            0 0 0 1px rgba(255, 255, 255, var(--wds-shadow-dark-border-opacity)) !important;
    }

    /* ── Main content container ──────────────────────────────────────────── */
    .fi-main-ctn {
        width: 100% !important;
        padding-left: var(--wds-space-outer) !important;
        padding-right: var(--wds-space-outer) !important;
        position: relative !important;
        box-sizing: border-box !important;
    }
    .fi-main {
        margin-inline: 0 !important;
        background-color: var(--wds-color-surface) !important;
        transition: opacity 0.25s ease-out, transform 0.25s ease-out;
        will-change: opacity, transform;
    }
    .dark .fi-main,
    .fi-main:where(.dark, .dark *),
    [data-theme="dark"] .fi-main {
        background-color: var(--wds-color-surface-dark) !important;
    }
    .fi-main-ctn .fi-main {
        max-width: none !important;
        /* Tablet: body scrolls naturally — no fixed height, no overflow clipping.
           Desktop (1024px+) overrides height/overflow once the body is locked. */
        margin: var(--wds-space-top) 0 var(--wds-space-bottom) !important;
        border-radius: var(--wds-radius-container) !important;
        background-color: var(--wds-color-surface) !important;
        position: relative !important;
        box-sizing: border-box !important;
        box-shadow:
            0 var(--wds-shadow-y) var(--wds-shadow-blur) var(--wds-shadow-spread)
                rgba(0, 0, 0, var(--wds-shadow-opacity)),
            0 0 0 1px rgba(0, 0, 0, var(--wds-shadow-border-opacity)) !important;
    }
    .dark .fi-main-ctn .fi-main,
    [data-theme="dark"] .fi-main-ctn .fi-main {
        background-color: var(--wds-color-surface-dark) !important;
        box-shadow:
            0 var(--wds-shadow-y) var(--wds-shadow-blur) var(--wds-shadow-spread)
                rgba(0, 0, 0, var(--wds-shadow-dark-opacity)),
            0 0 0 1px rgba(255, 255, 255, var(--wds-shadow-dark-border-opacity)) !important;
    }
    .fi-main-ctn .fi-main > *     { border-radius: inherit !important; }
    .fi-main-ctn .fi-main > * > * { border-radius: var(--wds-radius-content) !important; }
    .fi-body-has-top-navigation .fi-main-ctn .fi-main {
        margin-left:  0 !important;
        margin-right: 0 !important;
    }

    /* ── User menu ───────────────────────────────────────────────────────── */
    .fi-user-menu-trigger { border-radius: var(--wds-radius-container) !important; }

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
    .dark #database-notifications > div:nth-child(2) > div {
        background-color: var(--wds-color-surface-dark) !important;
    }

    /* ── Modals ──────────────────────────────────────────────────────────── */
    .fi-modal-overlay,
    .fi-modal-close-overlay {
        backdrop-filter: saturate(150%) brightness(0.6) !important;
        background: rgba(0, 0, 0, 0.6) !important;
        transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    .fi-modal-window {
        animation: wds-modal-spring 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        backdrop-filter: blur(25px) saturate(100%) !important;
        background: rgba(255, 255, 255, 0.9) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37) !important;
        border-radius: var(--wds-radius-container) !important;
    }
    .dark .fi-modal-window {
        background: rgba(0, 0, 0, 0.25) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.5) !important;
    }
    @keyframes wds-modal-spring {
        from { transform: scale(0.95) translateY(10px); opacity: 0; }
        to   { transform: scale(1)    translateY(0);    opacity: 1; }
    }

}
</style>
