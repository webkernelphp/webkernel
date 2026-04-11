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
