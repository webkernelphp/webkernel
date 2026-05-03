@props([
    'columns'    => 1,
    'grid'       => 12,
    'reverse'    => false,
    'hideHeader' => true,
    'fullHeight' => true,
    'offset'     => 'var(--wds-content-offset, 64px)',
])

@php
    $cols       = (int) $grid;
    $reverse    = filter_var($reverse,    FILTER_VALIDATE_BOOLEAN);
    $hideHeader = filter_var($hideHeader, FILTER_VALIDATE_BOOLEAN);
    $fullHeight = filter_var($fullHeight, FILTER_VALIDATE_BOOLEAN);
    $uid        = 'wds-db-' . substr(md5(uniqid()), 0, 8);
@endphp

<style>
    #{{ $uid }} {
        display: grid;
        grid-template-columns: repeat({{ $cols }}, 1fr);
        /* stretch columns to fill row height — this is what makes full-height work */
        align-items: stretch;
        width: 100%;
        gap: 0;
        @if($reverse)
        direction: rtl;
        @endif
    }

    @if($reverse)
    #{{ $uid }} > * {
        direction: ltr;
    }
    @endif

    @if($fullHeight)
    #{{ $uid }} {
        height: calc(100vh - {{ $offset }});
        max-height: calc(100vh - {{ $offset }});
        overflow: hidden;
    }

    /*
        Columns inside a full-height dashboard fill 100% of the parent height.
        overflow-y: auto allows independent scrolling per column.
        min-height: 0 is required in grid/flex children to prevent overflow blowout
        across Firefox, Safari, and Chrome.
    */
    #{{ $uid }} > .wcs-column,
    #{{ $uid }} > .wcs-column--tabs {
        height: 100%;
        max-height: 100%;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        overscroll-behavior: contain;
    }
    @endif

    @if($hideHeader)
    .fi-header,
    .fi-page-header          { display: none !important; }
    .fi-page-header-main-ctn { padding: 0 !important; }
    .fi-main                 { padding: 0 !important; max-width: 100% !important; }
    @endif

    /*
        Responsive collapse — applies to THIS dashboard instance only.
        Columns reorder via CSS order property (--wcs-mobile-order custom property
        set per column in column.blade.php).
    */
    @media (max-width: 768px) {
        #{{ $uid }} {
            grid-template-columns: 1fr !important;
            height: auto !important;
            max-height: none !important;
            overflow: visible !important;
        }

        #{{ $uid }} > .wcs-column,
        #{{ $uid }} > .wcs-column--tabs {
            height: auto !important;
            max-height: none !important;
            min-height: unset !important;
            /* order applied via --wcs-mobile-order in column.blade.php */
        }
    }
</style>

<div
    id="{{ $uid }}"
    {{ $attributes->merge(['class' => 'wds-dashboard']) }}
    data-grid="{{ $cols }}"
    data-reverse="{{ $reverse ? 'true' : 'false' }}"
>
    {{ $slot }}
</div>
