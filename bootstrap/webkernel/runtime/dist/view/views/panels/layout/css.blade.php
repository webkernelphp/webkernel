{{--
    webkernel::panels.layout.css
    ────────────────────────────
    Entry point injected at PanelsRenderHook::BODY_START (once per request, Octane-safe).

    Props:
      $sidebarKeepsBackground (bool)  — passed from FilamentRenderHooks
--}}
@include('webkernel::panels.layout._variables')
@include('webkernel::panels.layout._tablet')
@include('webkernel::panels.layout._table')
@include('webkernel::panels.layout._desktop', ['sidebarKeepsBackground' => $sidebarKeepsBackground])
<script>
@include('webkernel::panels.layout._script')
</script>
