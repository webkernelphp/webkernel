{{-- webkernel::panels.layout.topbar._base --}}
@php
    $topbarPaddingY      = '0.25rem';
    $topbarPaddingX      = '0.75rem';
    $topbarMinHeight     = '3.25rem';
    $topbarLogoHeight    = '1.75rem';
    $topbarAvatarSize    = '1.75rem';
@endphp

<style>
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

        /* Glassmorphism Logic */
        background-color: color-mix(in srgb, var(--wds-color-surface), transparent 20%) !important;
        backdrop-filter: blur(var(--wds-backdrop-blur, 12px)) !important;
        -webkit-backdrop-filter: blur(var(--wds-backdrop-blur, 12px)) !important;
        overflow: visible !important;
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

    /* Dark Mode specific fixes */
    .dark .fi-topbar,
    .fi-topbar:where(.dark, .dark *),
    [data-theme="dark"] .fi-topbar {
        background-color: color-mix(in srgb, var(--wds-color-surface), transparent 40%) !important;
        box-shadow:
            0 var(--wds-shadow-y) var(--wds-shadow-blur) var(--wds-shadow-spread)
                rgba(0, 0, 0, var(--wds-shadow-dark-opacity)),
            0 0 0 1px rgba(255, 255, 255, 0.1) !important; /* Bordure subtile pour l'effet verre */
    }
</style>
