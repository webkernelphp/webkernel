{{-- webkernel::panels.layout.topbar._colors — background surface light + dark (private partial) --}}
<style>
    .fi-topbar {
        background-color: var(--wds-color-surface) !important;
        backdrop-filter: blur(var(--wds-backdrop-blur)) !important;
        overflow: visible !important;
    }
    .dark .fi-topbar,
    .fi-topbar:where(.dark, .dark *),
    [data-theme="dark"] .fi-topbar {
        box-shadow:
            0 var(--wds-shadow-y) var(--wds-shadow-blur) var(--wds-shadow-spread)
                rgba(0, 0, 0, var(--wds-shadow-dark-opacity)),
            0 0 0 1px rgba(255, 255, 255, var(--wds-shadow-dark-border-opacity)) !important;
    }

</style>
