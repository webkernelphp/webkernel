@props([
    /*
    ┌─────────────────────────────────────────────────────────────────────────┐
    │ FILAMENT NATIVE TABS OVERRIDE                                           │
    │ Set filament-tabs="false" to leave Filament's tab design untouched.     │
    │ When false, none of the .fi-sc-tabs / .webkernel-dashboard-container    │
    │ rules are emitted. Use this when you want native Filament styling.       │
    └─────────────────────────────────────────────────────────────────────────┘
    */
    'filamentTabs'          => true,

    /*
    ┌─────────────────────────────────────────────────────────────────────────┐
    │ FILAMENT TAB MARGIN FIX                                                 │
    │ Fixes the first-tab margin jump caused by a Filament specificity        │
    │ conflict. Only relevant when filament-tabs="true".                      │
    │ Set to false to keep Filament's raw margin behaviour.                   │
    └─────────────────────────────────────────────────────────────────────────┘
    */
    'filamentTabMarginFix'  => true,

    /*
    ┌─────────────────────────────────────────────────────────────────────────┐
    │ FILAMENT TAB BACKGROUND OVERRIDE                                        │
    │ Resets .fi-sc-tabs.fi-contained background to transparent.             │
    │ Set to false to keep Filament's default contained background.           │
    └─────────────────────────────────────────────────────────────────────────┘
    */
    'filamentTabBackground' => true,

    /*
    ┌─────────────────────────────────────────────────────────────────────────┐
    │ DARK MODE SECTION FIX                                                   │
    │ Makes .fi-section transparent in dark mode.                             │
    └─────────────────────────────────────────────────────────────────────────┘
    */
    'darkSectionFix'        => true,

    /*
    ┌─────────────────────────────────────────────────────────────────────────┐
    │ CUSTOM SCROLLBARS                                                       │
    │ Thin webkit scrollbars on columns, tabs nav and content areas.          │
    │ Set to false to keep the browser default scrollbars.                    │
    └─────────────────────────────────────────────────────────────────────────┘
    */
    'customScrollbars'      => true,

    /*
    ┌─────────────────────────────────────────────────────────────────────────┐
    │ DESIGN TOKENS                                                           │
    │ Override any CSS custom property. Pass null to skip emitting it        │
    │ (useful when you set them globally in your own stylesheet).             │
    └─────────────────────────────────────────────────────────────────────────┘
    */
    'borderColor'           => 'rgba(156, 163, 175, 0.2)',
    'navWidth'              => '220px',
    'navItemRadius'         => '6px',
    'tabActiveBg'           => 'rgba(99, 102, 241, 0.08)',
    'tabActiveColor'        => 'rgb(99, 102, 241)',
    'tabColor'              => 'inherit',
    'tabHoverBg'            => 'rgba(156, 163, 175, 0.1)',
    'columnPadding'         => '0px',
    'itemPadding'           => '0px',
    'transition'            => '150ms ease',
])

@php
    $filamentTabs         = filter_var($filamentTabs,         FILTER_VALIDATE_BOOLEAN);
    $filamentTabMarginFix = filter_var($filamentTabMarginFix, FILTER_VALIDATE_BOOLEAN);
    $filamentTabBackground= filter_var($filamentTabBackground,FILTER_VALIDATE_BOOLEAN);
    $darkSectionFix       = filter_var($darkSectionFix,       FILTER_VALIDATE_BOOLEAN);
    $customScrollbars     = filter_var($customScrollbars,     FILTER_VALIDATE_BOOLEAN);
@endphp

<style>
/* =============================================================================
   WebKernel Design System — Dashboard assets
   Rendered props:
     filament-tabs             = {{ $filamentTabs ? 'true' : 'false' }}
     filament-tab-margin-fix   = {{ $filamentTabMarginFix ? 'true' : 'false' }}
     filament-tab-background   = {{ $filamentTabBackground ? 'true' : 'false' }}
     dark-section-fix          = {{ $darkSectionFix ? 'true' : 'false' }}
     custom-scrollbars         = {{ $customScrollbars ? 'true' : 'false' }}
============================================================================= */

/* -- Design tokens ---------------------------------------------------------- */
:root {
    @if($borderColor !== null)    --wcs-border-color:     {{ $borderColor }};     @endif
    @if($navWidth !== null)       --wcs-nav-width:        {{ $navWidth }};        @endif
    @if($navItemRadius !== null)  --wcs-nav-item-radius:  {{ $navItemRadius }};   @endif
    @if($tabActiveBg !== null)    --wcs-tab-active-bg:    {{ $tabActiveBg }};     @endif
    @if($tabActiveColor !== null) --wcs-tab-active-color: {{ $tabActiveColor }};  @endif
    @if($tabColor !== null)       --wcs-tab-color:        {{ $tabColor }};        @endif
    @if($tabHoverBg !== null)     --wcs-tab-hover-bg:     {{ $tabHoverBg }};      @endif
    @if($columnPadding !== null)  --wcs-column-padding:   {{ $columnPadding }};   @endif
    @if($itemPadding !== null)    --wcs-item-padding:     {{ $itemPadding }};     @endif
    @if($transition !== null)     --wcs-transition:       {{ $transition }};      @endif
}

/* -- Dashboard grid --------------------------------------------------------- */
.wds-dashboard {
    align-items: stretch;
}

/* -- Column ----------------------------------------------------------------- */
.wcs-column {
    display: flex;
    flex-direction: column;
    align-self: stretch;
    min-height: 0;
    overflow-x: hidden;
    overflow-y: auto;
    overscroll-behavior: contain;
    border-right: 1px solid var(--wcs-border-color);
}

.wcs-column:last-child {
    border-right: none;
}

/* -- Column items ----------------------------------------------------------- */
.wcs-column-item {
    padding: var(--wcs-item-padding);
    border-bottom: 1px solid var(--wcs-border-color);
    flex-shrink: 0;
}

.wcs-column-item:last-child {
    border-bottom: none;
}

/* -- Tab column wrapper ----------------------------------------------------- */
.wcs-column--tabs {
    display: flex;
    flex-direction: column;
    align-self: stretch;
    min-height: 0;
    overflow: hidden;
    border-right: 1px solid var(--wcs-border-color);
}

.wcs-column--tabs:last-child {
    border-right: none;
}

/* Allow Filament's own component to grow into the column height */
.wcs-column--tabs > * {
    flex: 1 1 0;
    min-height: 0;
}

/* -- WCS native tab layout (used when tabs prop is set on column) ----------- */
.wcs-tabs-layout {
    display: flex;
    flex-direction: row;
    flex: 1 1 0;
    min-height: 0;
    width: 100%;
}

.wcs-tabs-nav {
    display: flex;
    flex-direction: column;
    width: var(--wcs-nav-width);
    min-width: var(--wcs-nav-width);
    max-width: var(--wcs-nav-width);
    flex-shrink: 0;
    border-right: 1px solid var(--wcs-border-color);
    padding: 12px 8px;
    gap: 4px;
    overflow-y: auto;
    overflow-x: hidden;
    overscroll-behavior: contain;
}

.wcs-tabs-nav-item {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 9px 12px;
    border-radius: var(--wcs-nav-item-radius);
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--wcs-tab-color);
    background: transparent;
    border: none;
    cursor: pointer;
    text-align: left;
    transition: background var(--wcs-transition), color var(--wcs-transition);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    flex-shrink: 0;
    -webkit-appearance: none;
    appearance: none;
}

.wcs-tabs-nav-item:hover     { background: var(--wcs-tab-hover-bg); }
.wcs-tabs-nav-item--active   { background: var(--wcs-tab-active-bg); color: var(--wcs-tab-active-color); }

.wcs-tabs-content {
    flex: 1 1 0;
    min-width: 0;
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
    overscroll-behavior: contain;
}

.wcs-tabs-panel {
    padding: var(--wcs-column-padding);
}

@if($customScrollbars)
/* -- Custom scrollbars (webkit) --------------------------------------------- */
.wcs-column::-webkit-scrollbar,
.wcs-column--tabs::-webkit-scrollbar,
.wcs-tabs-nav::-webkit-scrollbar,
.wcs-tabs-content::-webkit-scrollbar,
.fi-sc-tabs::-webkit-scrollbar          { width: 4px; }

.wcs-column::-webkit-scrollbar-thumb,
.wcs-column--tabs::-webkit-scrollbar-thumb,
.wcs-tabs-nav::-webkit-scrollbar-thumb,
.wcs-tabs-content::-webkit-scrollbar-thumb,
.fi-sc-tabs::-webkit-scrollbar-thumb    { background: rgba(156, 163, 175, 0.3); border-radius: 2px; }
@endif

@if($filamentTabs)
/* =============================================================================
   Filament native tabs integration
   Controlled by filament-tabs prop (currently: enabled).
   Set filament-tabs="false" to skip this entire block and leave Filament
   tab styles completely untouched.
============================================================================= */

.webkernel-dashboard-container {
    display: flex !important;
    flex-direction: row-reverse !important;
    align-items: stretch !important;
    flex: 1 1 0;
    min-height: 0;
    height: 100%;
}

.webkernel-dashboard-container .fi-tabs-item-label {
    display: none;
}

.webkernel-dashboard-container nav {
    border-left: 1px solid var(--wcs-border-color);
}

.fi-sc-tabs.fi-contained {
    border-radius: 0 !important;
    height: 100%;
    overflow: hidden;
    @if($filamentTabBackground)
    background-color: var(--wds-color-surface, transparent) !important;
    @endif
}

@if($filamentTabBackground)
.fi-sc-tabs.fi-contained:where(.dark, .dark *) {
    background-color: var(--wds-color-surface-dark, transparent) !important;
}
@endif

@if($filamentTabMarginFix)
/*
    Margin fix — desktop.
    Filament specificity conflict causes first active tab to lose its top margin.
    We reset all tabs to 0, then re-apply 1rem on .fi-active with higher specificity.
    Does NOT touch nav button sizing or any flex property.
*/
.fi-sc-tabs.fi-contained .fi-sc-tabs-tab {
    margin: 0 !important;
    padding: 0 !important;
    gap: 0 !important;
}

.fi-sc-tabs.fi-contained .fi-sc-tabs-tab.fi-active {
    margin: 1rem !important;
    padding: 0 !important;
    gap: 0 !important;
}

.fi-sc-tabs.fi-contained .fi-sc-tabs-tab.activity-feed {
    margin: 0 !important;
    padding: 0 !important;
    gap: 0 !important;
}
@endif

@endif {{-- /filamentTabs --}}

@if($darkSectionFix)
/* -- Dark mode section background fix --------------------------------------- */
.fi-section:where(.dark, .dark *) {
    background-color: transparent;
}
@endif

[x-cloak] { display: none !important; }

/* =============================================================================
   Responsive
============================================================================= */
@media (max-width: 768px) {

    /*
        All columns get border-bottom on mobile regardless of DOM position.
        Reason: CSS `order` changes visual order but not :last-child resolution,
        so we cannot reliably suppress the last border via :last-child when
        mobile-first reordering is active. Always-on is the safe default.
    */
    .wcs-column,
    .wcs-column--tabs {
        width: 100% !important;
        height: auto !important;
        max-height: none !important;
        min-height: unset !important;
        border-right: none !important;
        border-bottom: 1px solid var(--wcs-border-color) !important;
        overflow-y: visible;
        overflow-x: hidden;
        order: var(--wcs-mobile-order, 0);
    }

    .wcs-column--tabs > * {
        flex: none;
    }

    .wcs-tabs-layout {
        flex-direction: column;
        height: auto;
        min-height: unset;
    }

    .wcs-tabs-nav {
        flex-direction: row;
        width: 100%;
        min-width: unset;
        max-width: unset;
        height: auto;
        overflow-x: auto;
        overflow-y: hidden;
        border-right: none;
        border-bottom: 1px solid var(--wcs-border-color);
        padding: 8px;
        gap: 4px;
        flex-wrap: nowrap;
        -webkit-overflow-scrolling: touch;
    }

    .wcs-tabs-nav-item  { flex-shrink: 0; }
    .wcs-tabs-content   { overflow-y: visible; min-height: unset; }

    @if($filamentTabs)
    .fi-sc-tabs.fi-contained {
        height: auto !important;
        min-height: unset !important;
        overflow: visible;
    }

    .webkernel-dashboard-container {
        height: auto !important;
        min-height: unset !important;
    }

    @if($filamentTabMarginFix)
    .fi-sc-tabs.fi-contained .fi-sc-tabs-tab.fi-active {
        margin: 0.5rem !important;
    }
    @endif

    .webkernel-dashboard-container .fi-tabs-item-label {
        display: none !important;
    }
    @endif {{-- /filamentTabs mobile --}}
}
</style>

<script>
/**
 * WebKernel Component System — wcsTabColumn
 * Parses [data-wcs-tab] children from a hidden source slot,
 * builds reactive tab state consumed by Alpine.js.
 */
function wcsTabColumn(columnId) {
    return {
        tabs:      [],
        activeTab: 0,

        init() {
            const container = document.getElementById(columnId);
            if (!container) return;

            const source = container.querySelector('.wcs-tabs-source');
            if (!source) return;

            this.tabs = Array.from(source.querySelectorAll('[data-wcs-tab]')).map(el => ({
                label:   el.dataset.label || '',
                content: el.innerHTML,
            }));
        },
    };
}
</script>
