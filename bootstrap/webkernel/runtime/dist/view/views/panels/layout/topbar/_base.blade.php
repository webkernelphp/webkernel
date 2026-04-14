{{-- webkernel::panels.layout.topbar._base
     sticky positioning, height, inner compactness (private partial)
     --}}
@php
    $topbarPaddingY      = '0.25rem';
    $topbarPaddingX      = '0.75rem';
    $topbarMinHeight     = '3.25rem';
    $topbarLogoHeight    = '1.75rem';
    $topbarAvatarSize    = '1.75rem';
@endphp
<style>
@media (min-width: 768px) {
    .fi-topbar-ctn {
        position: sticky !important;
        top: 0 !important;
        z-index: 20 !important;
    }
    .fi-topbar {
        padding-top:    {{ $topbarPaddingY }} !important;
        padding-bottom: {{ $topbarPaddingY }} !important;
        padding-left:   {{ $topbarPaddingX }} !important;
        padding-right:  {{ $topbarPaddingX }} !important;
        min-height: {{ $topbarMinHeight }} !important;
    }
    /* Logo */
    .fi-topbar .fi-logo {
        height: {{ $topbarLogoHeight }} !important;
    }
    /* Avatar */
    .fi-topbar .fi-avatar {
        width:  {{ $topbarAvatarSize }} !important;
        height: {{ $topbarAvatarSize }} !important;
    }
    /* User menu trigger */
    .fi-user-menu-trigger {
        padding: 0.15rem !important;
    }
}
</style>
