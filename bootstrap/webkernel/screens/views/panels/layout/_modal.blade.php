{{-- webkernel::panels.layout._modal — overlay backdrop + window glass + spring animation (private partial) --}}
<style>
@media (min-width: 768px) {
    .fi-modal>.fi-modal-window-ctn {
        display: unset;
    }

    .fi-modal-overlay,
    .fi-modal-close-overlay {
        backdrop-filter: saturate(150%) brightness(0.6) !important;
        background: rgba(0, 0, 0, 0.6) !important;
        transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    .fi-modal-window {
        animation: wds-modal-spring 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        backdrop-filter: blur(25px) saturate(100%) !important;
        background: rgba(255, 255, 255, 0.9) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37) !important;
    }
    .dark .fi-modal-window {
        background: rgba(0, 0, 0, 0.25) !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.5) !important;
    }
    @keyframes wds-modal-spring {
        from { transform: scale(0.95) translateY(10px); opacity: 0; }
        to   { transform: scale(1)    translateY(0);    opacity: 1; }
    }
}
</style>
