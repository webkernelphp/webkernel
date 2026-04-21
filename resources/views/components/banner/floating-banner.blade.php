@php
    // Close button only if ALL warnings are closeable
    $closeable = collect($warnings)->every(fn($w) => $w['closeable']);
    $allIds    = collect($warnings)->pluck('id')->toJson();
@endphp

<style>
    @keyframes wkt-ticker {
        0%   { transform: translateX(0); }
        100% { transform: translateX(-10%); }
    }
    #wkt-banner-track {
        display: inline-flex;
        white-space: nowrap;
        animation: wkt-ticker {{ $speed ?? 20 }}s linear infinite;
    }
    #wkt-banner-track:hover {
        animation-play-state: paused;
    }
</style>

{{-- Rendered visible immediately — Alpine only hides if already dismissed --}}
<div
    id="wkt-banner"
    x-data="{
        ids: {{ $allIds }},
        storageKey: 'wkt-banners-dismissed',
        dismiss() {
            const closed = JSON.parse(localStorage.getItem(this.storageKey) || '[]');
            this.ids.forEach(id => { if (!closed.includes(id)) closed.push(id); });
            localStorage.setItem(this.storageKey, JSON.stringify(closed));
            document.getElementById('wkt-banner').style.display = 'none';
        }
    }"
    x-init="
        const closed = JSON.parse(localStorage.getItem(storageKey) || '[]');
        if (ids.every(id => closed.includes(id))) $el.style.display = 'none';
    "
    style="display: flex; align-items: center; overflow: hidden; "
>
    <div style="flex: 1; overflow: hidden;">
        <div id="wkt-banner-track">
            @for ($i = 0; $i < 10; $i++)
                @foreach ($warnings as $w)
                    <span style="
                        display: inline-flex;
                        align-items: center;
                        padding: 3px 14px 3px 10px;
                        background-image: linear-gradient(to right, {{ $w['start_color'] }}, {{ $w['end_color'] }});
                        color: #fff;
                        font-size: 13px;
                    ">{!! $w['content'] !!}</span>
                @endforeach
            @endfor
        </div>
    </div>

    @if ($closeable)
        <div style="flex-shrink: 0; padding: 0 12px; display: flex; align-items: center;">
            <x-filament::icon
                x-on:click="dismiss()"
                alias="banner::close"
                icon="heroicon-m-x-mark"
                style="width: 1rem; height: 1rem; cursor: pointer; color: #fff; opacity: 0.7;"
            />
        </div>
    @endif
</div>
