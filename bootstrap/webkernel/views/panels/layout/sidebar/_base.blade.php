{{-- webkernel::panels.layout.sidebar._base — internal padding, border-right, hidden scrollbar (private partial) --}}
<style>
.fi-sidebar-nav {
    padding-left:       var(--wds-sidebar-padding-x) !important;
    padding-right:      var(--wds-sidebar-padding-x) !important;
    padding-inline:     0 !important;
    position:           relative !important;
    overflow-y:         auto !important;
    overflow-x:         auto !important;
    scrollbar-width:    none !important;
    -ms-overflow-style: none !important;
}
.fi-sidebar-nav::-webkit-scrollbar { width: 0 !important; height: 0 !important; }
.fi-sidebar-nav-groups  { row-gap: calc(var(--spacing) * 2.5); }
.fi-sidebar-footer      { row-gap: calc(var(--spacing) * 2.5); }
</style>
