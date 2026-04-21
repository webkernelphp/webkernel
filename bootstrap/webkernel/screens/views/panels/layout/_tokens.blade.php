{{-- webkernel::panels.layout._tokens — :root design tokens (private partial) --}}
<style>
:root {
    /* ── Spacing ── */
    --wds-space-outer:  0.65rem;
    --wds-space-inner:  0.65rem;
    --wds-space-top:    0.65rem;
    --wds-space-bottom: 0.65rem;
    --wds-bottom-clearance: 3.6rem;

    /* ── Component heights ── */
    --wds-topbar-height:         2rem;
    --wds-sidebar-toggle-height: 2.5rem;

    /* ── Border-radius ── */
    --wds-radius-container: 7px;
    --wds-radius-content:   13px;

    /* ── Effects ── */
    --wds-backdrop-blur: 10px;

    /* ── Shadows (light) ── */
    --wds-shadow-y:              2px;
    --wds-shadow-blur:           4px;
    --wds-shadow-spread:         0px;
    --wds-shadow-opacity:        0.06;
    --wds-shadow-border-opacity: 0.08;

    /* ── Shadows (dark) ── */
    --wds-shadow-dark-opacity:        0.3;
    --wds-shadow-dark-border-opacity: 0.08;

    /* ── Scrollbar ── */
    --wds-scrollbar-size:          3.5px;
    --wds-scrollbar-opacity:       0.7;
    --wds-scrollbar-opacity-hover: 0.9;

    /* ── Colors (light) ── */
    --wds-color-background: #e4e7e9;
    /*--wds-color-surface:    oklch(96.8% 0.007 247.896);*/
    --wds-color-topbar:     oklch(96.8% 0.007 247.896);

    /* ── Sidebar inner spacing ── */
    --wds-sidebar-padding-x:         1rem;
    --wds-sidebar-item-margin-left:  1rem;
    --wds-sidebar-item-margin-right: 0.8rem;

    /* ── Content-offset helpers ── */
    --wds-content-offset-with-topbar: calc(
        var(--wds-topbar-height)
        + (var(--wds-space-top) * 3)
        + var(--wds-space-bottom)
        + var(--wds-bottom-clearance)
    );
    --wds-content-offset-without-topbar: calc(
        (var(--wds-space-top) * 2)
        + var(--wds-space-bottom)
        + var(--wds-bottom-clearance)
    );
    --wds-content-offset-with-toggle: calc(
        var(--wds-sidebar-toggle-height)
        + var(--wds-space-top)
        + var(--wds-space-bottom)
        + var(--wds-bottom-clearance)
    );

    /* Active offset — overridden contextually via :has() in sidebar/_desktop */
    --wds-content-offset: var(--wds-content-offset-with-topbar);
    --wds-sidebar-height: calc(100vh - var(--wds-content-offset));
}

/* ── Dark mode token overrides ── */
.dark,
[data-theme="dark"] {
    --wds-color-background: oklch(21% 0.006 285.885);
    --wds-color-surface:    var(--gray-950);
    --wds-color-topbar:     var(--gray-950);
}
</style>
