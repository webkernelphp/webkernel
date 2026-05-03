{{--
    webkernel::panels.layout.css
    ────────────────────────────
    Entry point — includes only, no styles here.
    Injected at PanelsRenderHook::BODY_START (once per request, Octane-safe).
--}}

{{-- Design tokens --}}
@includeIf('webkernel::panels.layout._tokens')
@includeIf('webkernel::panels.layout._typography')

{{-- Page shell --}}
@includeIf('webkernel::panels.layout._page')

{{-- Topbar --}}
@includeIf('webkernel::panels.layout.topbar._base')
@includeIf('webkernel::panels.layout.topbar._colors')

{{-- Sidebar --}}
@includeIf('webkernel::panels.layout.sidebar._base')
@includeIf('webkernel::panels.layout.sidebar._items')
@includeIf('webkernel::panels.layout.sidebar._desktop')

{{-- Main content area --}}
@includeIf('webkernel::panels.layout._main')

{{-- Global components --}}
@includeIf('webkernel::panels.layout._table')
@includeIf('webkernel::panels.layout._scrollbar')
@includeIf('webkernel::panels.layout._modal')

{{-- JS hooks --}}
@includeIf('webkernel::panels.layout._script')


@if(false)

    <style>
    .fi-page-main::before {
        content: "";
        display: block;
        height: 200px; /* hauteur fixe ou ajustable */
        background-image: url("https://images.blush.design/b4cde6b173a7719b63c170a6a9f58ee0?w=920&auto=compress&cs=srgb");
        background-size: contain;     /* garde les proportions */
        background-repeat: no-repeat;
        background-position: center;
    }

    /* Variante en dark mode : inversion des couleurs */
    .dark .fi-page-main::before,
    [data-theme="dark"] .fi-page-main::before {
        filter: invert(0.7) brightness(1.2) contrast(0.9);
    }


    </style>
@endif
