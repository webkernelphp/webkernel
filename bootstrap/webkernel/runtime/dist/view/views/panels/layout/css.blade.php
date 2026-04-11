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
@include('webkernel::panels.layout._desktop-fi-main')

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap" rel="stylesheet">

<style>
.fi-header-heading {
    font-family: "DM Sans", sans-serif;
    font-optical-sizing: auto;
    font-weight: 600;
    font-style: normal;
}
</style>
{{-- Script JS --}}
@include('webkernel::panels.layout._script')
