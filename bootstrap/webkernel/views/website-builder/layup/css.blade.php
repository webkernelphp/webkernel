{{--
    webkernel::website-builder.layup.css
    ─────────────────────────────────────
    Standalone entry point for Layup Page Builder CSS.
    Include via:
        @include('webkernel::website-builder.layup.css')
    or inject from PHP:
        view('webkernel::website-builder.layup.css')->render()

    All selectors use the `.lyp-` prefix to avoid collisions.
--}}
<style>
@include('webkernel::website-builder.layup._components')
</style>
