{{--
    Webkernel QuickTouch
    ════════════════════════════════════════════════════════════════════════
    Floating assistant panel injected at BODY_END in every registered panel.

    View data is assembled by \Webkernel\QuickTouch\QuickTouch::buildViewData()
    and passed here via the render-hook closure.

    Sub-views (all resolved via HasSelfResolvedView namespace):
      • partials/button.blade.php
      • partials/panel.blade.php
      • partials/context-menu.blade.php
      • partials/scripts.blade.php

    Location: bootstrap/webkernel/backend/quick_touch/quick-touch.blade.php
--}}
@if($wktEnabled && filament()->auth()->check())

    {{-- ── CSS custom properties + utility styles ──────────────────────── --}}
    @include('webkernel-quick-touch::partials.styles')

    {{-- ── Inline JS data bridge ──────────────────────────────────────── --}}
    <script>
        window.wktPanels    = {!! $wktPanelsJson !!};
        window.wktFavorites = {!! $wktFavoritesJson !!};
        window.wktUser      = {!! $wktUserJson !!};
        window.wktHasTrait  = {{ $wktHasTrait ? 'true' : 'false' }};
        window.wktGlobalActions  = {!! json_encode(array_map(fn($a) => $a->toArray(), $wktGlobalActions),  JSON_UNESCAPED_SLASHES) !!};
        window.wktContextItems   = {!! json_encode(array_map(fn($i) => $i->toArray(), $wktContextItems),   JSON_UNESCAPED_SLASHES) !!};
    </script>

    {{-- ── Root wrapper ────────────────────────────────────────────────── --}}
    <div id="webkernel-touch-root">

        {{-- Floating button --}}
        @include('webkernel-quick-touch::partials.button')

        {{-- Slide-over panel --}}
        @include('webkernel-quick-touch::partials.panel', [
            'wktUser'      => $wktUser,
            'wktPanels'    => $wktPanels,
            'wktFavorites' => $wktFavorites,
        ])

    </div>

    {{-- Standalone context menu (outside root so it can overflow freely) --}}
    @include('webkernel-quick-touch::partials.context-menu')

    {{-- All behaviour JS (loaded once per page) --}}
    @once
        @include('webkernel-quick-touch::partials.scripts')
    @endonce

@endif
