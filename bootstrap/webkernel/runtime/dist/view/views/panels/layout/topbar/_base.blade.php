{{-- webkernel::panels.layout.topbar._base — sticky positioning, z-index (private partial) --}}
<style>
@media (min-width: 768px) {
    .fi-topbar-ctn {
        position: sticky !important;
        top: 0 !important;
        z-index: 20 !important;
    }
    .fi-topbar {
        height: var(--wds-topbar-height) !important;
        overflow: visible !important;
    }
}
</style>
