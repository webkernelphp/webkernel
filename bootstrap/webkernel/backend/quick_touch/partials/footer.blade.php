@php
    use Filament\Support\Enums\Size;
    use Filament\Support\Enums\IconSize;
@endphp

<style>
    .version-label { margin:0!important; padding-left:8px; text-transform:uppercase; font-size:10px !important; cursor:default; }
    .version-label .full { display:none; }
    .version-label:hover .abbr { display:none;}
    .version-label:hover .full { display:inline; }
</style>

<div style="display:flex;justify-content:space-between;align-items:center;width:100%;">
    <span class="fi-filament-info-widget-version version-label">
        <span class="abbr">
            W {{ WEBKERNEL_CODENAME }} {{ strtoupper(substr(WEBKERNEL_CHANNEL,0,1)) }} v{{ WEBKERNEL_VERSION }}
        </span>
        <span class="full">
            Webkernel {{ WEBKERNEL_CODENAME }} {{ WEBKERNEL_CHANNEL }} v{{ WEBKERNEL_VERSION }}
        </span>
    </span>

    <div style="display:flex;gap:6px;align-items:center;visibility: hidden;">
        <x-filament::icon-button
            icon="cog"
            color="gray"
            tooltip="Settings"
            :size="Size::Small"
            :icon-size="IconSize::Small"
            type="button"
            tag="button">
        </x-filament::icon-button>

        <x-filament::icon-button
            icon="info"
            color="gray"
            tooltip="About QuickTouch"
            :size="Size::Small"
            :icon-size="IconSize::Small"
            type="button"
            tag="button">
        </x-filament::icon-button>

        <x-filament::icon-button
            icon="traffic-cone"
            color="gray"
            tooltip="Keep it open"
            :size="Size::Small"
            :icon-size="IconSize::Small"
            type="button"
            tag="button">
        </x-filament::icon-button>
    </div>
</div>
