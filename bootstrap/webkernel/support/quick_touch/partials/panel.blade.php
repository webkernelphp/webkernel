{{--
    bootstrap/webkernel/backend/quick_touch/quick_touch/partials/panel.blade.php

    Renders the slide-over panel using native Filament Blade components:
      <x-filament::tabs>               — tab strip
      <x-filament::tabs.item>          — individual tab
      <x-filament::badge>              — small count / label badges
      <x-filament::button>             — footer action buttons
      <x-filament::icon-button>        — icon-only footer buttons
      <x-filament::link>               — styled anchor items
      <x-filament::section>            — collapsible section wrapper
      <x-filament::loading-indicator>  — async spinner

    The Tabs component renders a <div role="tablist"> with proper ARIA.
    Tab content is toggled via JS (wkt-tab-active class) to keep everything
    in the DOM and avoid flicker on Livewire navigations.
--}}

@php
    $initials = '';
    if (!empty($wktUser['name'])) {
        $parts = explode(' ', trim($wktUser['name']));
        $initials = strtoupper(substr($parts[0],0,1) . (isset($parts[1]) ? substr($parts[1],0,1) : ''));
    } elseif (!empty($wktUser['email'])) {
        $initials = strtoupper(substr($wktUser['email'],0,2));
    }
    $hasPanels = count($wktPanels) > 1;

    $quickTouchVersion = \Webkernel\QuickTouch\QuickTouch::version();
@endphp

<div
    id="webkernel-touch-panel"
    role="dialog"
    aria-modal="true"
    aria-label="Webkernel QuickTouch">

    {{-- ── Header: logo + title + user chip ─────────────────────────── --}}
    <div style="border-bottom:1px solid var(--wkt-divider); flex-shrink:0;">

        <div style="display:flex; align-items:center; gap:8px; padding:7px 14px 8px;">
            @include('webkernel-quick-touch::quick-touch-logo')

            <div style="display:flex; align-items:center; justify-content:space-between; width:100%;">

                <div style="display:flex; flex-direction:column;">
                    <span style="font-size:14px; font-weight:600; color:var(--wkt-text); letter-spacing:0.01em;">
                        QuickTouch V{{ $quickTouchVersion }}
                    </span>
                    <span id="wkt-page-info"
                          style="font-size:11px; color:var(--wkt-muted); max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    </span>
                </div>

                @php
                    use Filament\Support\Enums\Size;
                    use Filament\Support\Enums\IconSize;

                    $buttons = [
                        [
                            'icon' => 'heroicon-m-minus',
                            'id' => '',
                            'color' => 'success',
                            'tooltip' => 'Minimize',
                            'label' => 'Minimize',
                            'show'=> false,
                        ],
                        [
                            'icon' => 'maximize-2',
                            'id' => '',
                            'color' => 'warning',
                            'tooltip' => 'Maximize',
                            'label' => 'Maximize',
                            'show'=> false,
                        ],
                        [
                            'icon' => 'heroicon-m-x-mark',
                            'id' => 'wkt-close-btn',
                            'color' => 'danger',
                            'tooltip' => 'Close',
                            'label' => 'Close',
                            'show'=> true,
                        ],
                    ];
                @endphp

                <div style="display:flex; gap:1.1rem; margin-left:auto;">
                    @foreach($buttons as $btn)
                        @if($btn['show'])
                            <x-filament::icon-button
                                :icon="$btn['icon']"
                                :color="$btn['color']"
                                :tooltip="$btn['tooltip']"
                                :size="Size::Small"
                                :icon-size="IconSize::Small"
                                id="{{ $btn['id'] ?? null }}"
                                type="button"
                                tag="button"
                                style="border:1px solid var(--wkt-divider); border-radius:4px; padding:2px;">
                                    <span style="color: var(--gray-400) !important; opacity: .6 !important;">
                                        {{ $btn['label'] }}
                                    </span>
                            </x-filament::icon-button>
                        @endif
                    @endforeach
                </div>

            </div>
        </div>

    </div>


    {{-- ── Filament Tabs ──────────────────────────────────────────────── --}}

    @php
        $tabs = [
            [
                'key' => 'main',
                'label' => 'Main',
                'visible' => true,
            ],
            [
                'key' => 'context',
                'label' => 'Context',
                'visible' => true,
            ],
            [
                'key' => 'panels',
                'label' => 'Panels',
                'visible' => $hasPanels,
                'badge' => $hasPanels ? count($wktPanels) : null,
            ],
        ];
    @endphp

    <div style="border-bottom:1px solid var(--wkt-divider); flex-shrink:0; width:100%!important;">
        <x-filament::tabs
            class="wkt-force-tabs"
            style="
                display:flex!important;
                width:100%!important;
                gap:0!important;

                background:transparent!important;
                border:none!important;
                border-radius:0!important;
                box-shadow:none!important;
                padding:0!important;
            "
        >
            @foreach ($tabs as $tab)
                <x-filament::tabs.item
                    x-show="{{ $tab['visible'] ? 'true' : 'false' }}"
                    :active="$loop->first"
                    alpine-active="wktTab==='{{ $tab['key'] }}'"
                    x-on:click="wktTab='{{ $tab['key'] }}'"
                    wire:click.prevent
                    id="wkt-tab-btn-{{ $tab['key'] }}"
                    style="
                        flex:1 1 0%!important;
                        width:100%!important;
                        max-width:none!important;

                        margin:0!important;
                        padding:.4rem .5rem!important;

                        border:none!important;
                        outline:none!important;
                        box-shadow:none!important;
                        border-radius:0!important;

                        display:flex!important;
                        align-items:center!important;
                        justify-content:center!important;

                        border-right:1px solid var(--wkt-divider)!important;

                        font-size: 0.775rem !important;
                    "
                >
                    {{ $tab['label'] }}

                    @if(!empty($tab['badge']))
                        <x-slot name="badge">
                            {{ $tab['badge'] }}
                        </x-slot>
                    @endif
                </x-filament::tabs.item>
            @endforeach
        </x-filament::tabs>
    </div>
    {{-- ── Tab content (scroll area) ─────────────────────────────────── --}}
    <div class="wkt-scroll">

        {{-- ─ TAB: Main ──────────────────────────────────────────────── --}}
        <div id="wkt-tab-main" class="wkt-tab-pane">

            <div class="wkt-section-label">Favorites</div>
            <div id="wkt-favorites-list">
                <div class="wkt-item"
                     style="color:var(--wkt-muted);font-size:12px;padding:8px 16px;cursor:default;">
                    No favorites yet — add from the Context tab.
                </div>
            </div>

            <div class="wkt-divider"></div>

            <div class="wkt-section-label">Page</div>
            <div id="wkt-page-actions">
                {{-- JS-rendered per-page / per-resource actions land here --}}
            </div>

        </div>

        {{-- ─ TAB: Context ────────────────────────────────────────────── --}}
        <div id="wkt-tab-context" class="wkt-tab-pane" style="display:none;">

            <div class="wkt-section-label">Navigation</div>

            <button class="wkt-item" onclick="window.history.back()">
                <span class="wkt-item-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
                </span>
                <span class="wkt-item-label">Go back</span>
            </button>

            <button class="wkt-item" onclick="window.history.forward()">
                <span class="wkt-item-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
                </span>
                <span class="wkt-item-label">Go forward</span>
            </button>

            <button class="wkt-item" onclick="window.location.reload()">
                <span class="wkt-item-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px" aria-hidden="true"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                </span>
                <span class="wkt-item-label">Refresh page</span>
            </button>

            <div class="wkt-divider"></div>

            <button class="wkt-item"
                    onclick="navigator.clipboard&&navigator.clipboard.writeText(window.location.href)">
                <span class="wkt-item-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                </span>
                <span class="wkt-item-label">Copy URL</span>
            </button>

            <div class="wkt-divider"></div>
            <div class="wkt-section-label">Favorites</div>

            <button class="wkt-item" id="wkt-add-fav">
                <span class="wkt-item-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                </span>
                <span class="wkt-item-label">Add this page to favorites</span>
            </button>

            {{-- Extra context items registered via ContextMenuRegistry --}}
            <div id="wkt-extra-ctx-items"></div>

        </div>

        {{-- ─ TAB: Panels ─────────────────────────────────────────────── --}}
        <div id="wkt-tab-panels" class="wkt-tab-pane" style="display:none;">
            <div class="wkt-section-label">Switch panel</div>
            <div id="wkt-panels-list">
                @foreach($wktPanels as $panel)
                    <a href="{{ $panel['url'] }}" class="wkt-item">
                        <span class="wkt-item-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        </span>
                        <span class="wkt-item-label">{{ $panel['label'] }}</span>
                        <x-filament::badge size="sm" color="gray">panel</x-filament::badge>
                    </a>
                @endforeach
            </div>
        </div>

    </div>{{-- /.wkt-scroll --}}

    {{-- ── Quick-action grid ──────────────────────────────────────────── --}}
    @php
        $actions = [
            [
                'label' => 'Back',
                'icon' => 'chevron-left',
                'onclick' => 'window.history.back()',
            ],
            [
                'label' => 'Forward',
                'icon' => 'chevron-right',
                'onclick' => 'window.history.forward()',
            ],
            [
                'label' => 'Refresh',
                'icon' => 'rotate-ccw',
                'onclick' => 'window.location.reload()',
            ],
            [
                'label' => 'Top',
                'icon' => 'chevron-up',
                'onclick' => "window.scrollTo({top:0,behavior:'smooth'})",
            ],
        ];
    @endphp

    <div style="flex-shrink:0; display:flex;border-top:1px solid var(--wkt-divider); gap:0!important; padding:0!important; width:100%!important;">
        @foreach ($actions as $action)
            <x-filament::button
                :onclick="$action['onclick']"
                tag="button"
                size="xs"
                color="gray"
                :icon="$action['icon']"
                outlined
                class="w-full"
                style="
                    padding-block: .4rem .5rem !important;

                    flex:1 1 0%!important;
                    width:100%!important;
                    max-width:none!important;

                    border:none!important;
                    outline:none!important;
                    box-shadow:none!important;
                    border-radius:0!important;

                    margin:0!important;
                    border-right:1px solid var(--wkt-divider)!important;
                "
                wire:loading.attr="disabled"
            >
                <span style="color: var(--gray-400) !important; opacity: 1 !important;">
                    {{ $action['label'] }}
                </span>
            </x-filament::button>
        @endforeach

        <div id="wkt-extra-quick-actions" style="display:contents;"></div>
    </div>
    {{-- ── Footer ─────────────────────────────────────────────────────── --}}
    <div class="wkt-footer">
        @include('webkernel-quick-touch::partials.footer')
    </div>
</div>{{-- /#webkernel-touch-panel --}}
