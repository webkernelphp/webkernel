@props([
    'span'        => null,
    'tabs'        => false,
    'mobileFirst' => false,
    'mobileOrder' => null,
])

@php
    $hasTabs       = filter_var($tabs,        FILTER_VALIDATE_BOOLEAN) || $tabs === '' || $tabs === true;
    $isMobileFirst = filter_var($mobileFirst, FILTER_VALIDATE_BOOLEAN) || $mobileFirst === '' || $mobileFirst === true;
    $spanVal       = $span ? (int) $span : null;
    $gridStyle     = $spanVal ? "grid-column: span {$spanVal};" : '';

    /*
        Mobile order resolution:
          mobile-first        → -1  (floats to top of stacked layout)
          mobile-order="N"    → N   (explicit integer)
          neither             →  0  (follows DOM order, which is the default)

        This value is written as a CSS custom property --wcs-mobile-order
        and consumed by `order: var(--wcs-mobile-order, 0)` in the responsive
        media query inside dashboard-assets.blade.php.
    */
    $orderVal  = $isMobileFirst ? -1 : ($mobileOrder !== null ? (int) $mobileOrder : 0);
    $inlineStyle = trim("{$gridStyle} --wcs-mobile-order: {$orderVal};");

    $columnId = 'wcs-col-' . substr(md5(uniqid()), 0, 8);
@endphp

@if($hasTabs)

    <div
        class="wcs-column wcs-column--tabs"
        style="{{ $inlineStyle }}"
        id="{{ $columnId }}"
        x-data="wcsTabColumn('{{ $columnId }}')"
        x-init="init()"
    >
        {{-- Hidden source: Alpine reads [data-wcs-tab] elements from here --}}
        <div class="wcs-tabs-source" style="display:none;" aria-hidden="true">
            {{ $slot }}
        </div>

        <div class="wcs-tabs-layout">
            <nav class="wcs-tabs-nav" role="tablist" aria-orientation="vertical">
                <template x-for="(tab, index) in tabs" :key="index">
                    <button
                        class="wcs-tabs-nav-item"
                        :class="{ 'wcs-tabs-nav-item--active': activeTab === index }"
                        @click="activeTab = index"
                        :aria-selected="activeTab === index"
                        role="tab"
                        type="button"
                        x-text="tab.label"
                    ></button>
                </template>
            </nav>

            <div class="wcs-tabs-content">
                <template x-for="(tab, index) in tabs" :key="index">
                    <div
                        role="tabpanel"
                        x-html="tab.content"
                        x-show="activeTab === index"
                        class="wcs-tabs-panel"
                    ></div>
                </template>
            </div>
        </div>
    </div>

@else

    <div
        class="wcs-column"
        style="{{ $inlineStyle }}"
    >
        {{ $slot }}
    </div>

@endif
