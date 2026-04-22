{{-- webkernel::panels.layout._main — fi-main surface card (tablet) + fixed layout (desktop) (private partial) --}}
<style>
:root {
    --radius-xl: 6px;
    --radius-lg: 6px;
}

@media (min-width: 768px) {
    .fi-main { margin-inline: 0 !important; transition: opacity 0.25s ease-out, transform 0.25s ease-out; will-change: opacity, transform; }
    .fi-main-ctn .fi-main { max-width: none !important; background-color: var(--wds-color-surface) !important; position: relative !important; box-sizing: border-box !important; box-shadow: 0 var(--wds-shadow-y) var(--wds-shadow-blur) var(--wds-shadow-spread) rgba(0, 0, 0, var(--wds-shadow-opacity)), 0 0 0 1px rgba(0, 0, 0, var(--wds-shadow-border-opacity)) !important; }

    .dark .fi-main-ctn .fi-main, [data-theme="dark"] .fi-main-ctn .fi-main { background-color: var(--wds-color-surface-dark) !important; box-shadow: 0 var(--wds-shadow-y) var(--wds-shadow-blur) var(--wds-shadow-spread) rgba(0, 0, 0, var(--wds-shadow-dark-opacity)), 0 0 0 1px rgba(255, 255, 255, var(--wds-shadow-dark-border-opacity)) !important; }

    .fi-main-ctn .fi-main > *     { border-radius: inherit !important; }
    .fi-main-ctn .fi-main > * > * { border-radius: var(--wds-radius-content) !important; }
}
</style>
