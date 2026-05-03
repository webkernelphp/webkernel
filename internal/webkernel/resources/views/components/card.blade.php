{{--
    x-webkernel::card
    ──────────────────────────────────────────────────────────────────────────
    Structural surface primitive. Wraps <x-filament::section compact> under
    the hood so it inherits all Filament theming for free.

    Slots
      leading   — left column: icon, avatar, indicator
      header    — title block
      content   — main body: values, progress bars, anything
      meta      — right-aligned: stats, badges, secondary values
      footer    — bottom strip: actions or secondary info

    Props
      compact     bool    passed through to fi-section (default: true)
      disabled    bool    mutes the card, blocks interaction
      interactive bool    hover elevation + pointer
      href        string  makes the whole card a link
      target      string  link target, only with href
      clickable   bool    interactive without href
      tone        string  emerald | amber | red | blue | gray — left accent border

    Layout
      [ leading ] [ header / content stacked ] [ meta ]
                         [ footer ]

    Grid collapses gracefully:
      leading + meta  -> 3 cols (auto 1fr auto)
      meta only       -> 2 cols (1fr auto)
      neither         -> 1 col  (1fr)
--}}

@php
    $hasLeading = isset($leading);
    $hasMeta    = isset($meta);
    $hasFooter  = isset($footer);

    if ($hasLeading && $hasMeta) {
        $gridCols = 'auto 1fr auto';
    } elseif ($hasLeading) {
        $gridCols = 'auto 1fr';
    } elseif ($hasMeta) {
        $gridCols = '1fr auto';
    } else {
        $gridCols = '1fr';
    }

    $toneAccents = [
        'emerald' => 'rgb(16,185,129)',
        'amber'   => 'rgb(245,158,11)',
        'red'     => 'rgb(239,68,68)',
        'blue'    => 'rgb(59,130,246)',
        'gray'    => 'rgb(156,163,175)',
    ];
    $rootCss = '';

    $wrapperTag   = (isset($href) && $href) ? 'a' : 'div';
    $interactClass = (isset($interactive) && $interactive) || (isset($href) && $href) || (isset($clickable) && $clickable)
        ? 'wcs-card--interactive'
        : '';
    $disabledClass = (isset($disabled) && $disabled) ? 'wcs-card--disabled' : '';
@endphp

<{{ $wrapperTag }}
    class="wcs-card {{ $interactClass }} {{ $disabledClass }}"
    style="{{ $rootCss }}"
    @if(isset($href) && $href)
        href="{{ $href }}"
        @if(isset($target) && $target) target="{{ $target }}" @endif
    @endif
    @if($interactClass && !isset($href))
        role="button" tabindex="0"
    @endif
>
    <x-filament::section :compact="!(isset($compact) && $compact === false)">

        {{-- Main row: leading / central / meta --}}
        <div style="display: grid; grid-template-columns: {{ $gridCols }}; gap: 1rem; align-items: center;">

            @if($hasLeading)
                <div class="wcs-card__leading">{{ $leading }}</div>
            @endif

            {{-- Central column --}}
            <div class="wcs-card__main" style="display: flex; flex-direction: column; gap: 0.35rem; min-width: 0;">
                @if(isset($header))
                    <div class="wcs-card__header">{{ $header }}</div>
                @endif

                <div class="wcs-card__content">
                    {{ $slot }}
                </div>
            </div>

            @if($hasMeta)
                <div class="wcs-card__meta " style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.25rem; flex-shrink: 0;">
                    {{ $meta }}
                </div>
            @endif

        </div>

        @if($hasFooter)
            <div class="wcs-card__footer" style="margin-top: 0.75rem; padding-top: 0.6rem; border-top: 1px solid rgb(var(--gray-200));">
                {{ $footer }}
            </div>
        @endif

    </x-filament::section>
</{{ $wrapperTag }}>


{{--
    x-webkernel::card — stylesheet
    Included once per page via @once inside card/index.blade.php
    or registered globally in your asset stack.
--}}
@once('wcs-card-styles')
<style id="wcs-card-styles">

    /* ── Wrapper (sits outside fi-section, handles interactivity) ────────── */
    .wcs-card {
        display: block;
        text-decoration: none;
        color: inherit;
    }
    .wcs-card--interactive {
        cursor: pointer;
        transition: box-shadow 0.15s ease, transform 0.1s ease;
    }
    .wcs-card--interactive:hover > .fi-section {
        box-shadow: 0 2px 8px rgba(0,0,0,.08);
        transform: translateY(-1px);
    }
    .wcs-card--interactive:focus-visible {
        outline: 2px solid rgb(var(--primary-500));
        outline-offset: 2px;
        border-radius: var(--radius-lg, 0.5rem);
    }
    .wcs-card--disabled {
        opacity: 0.42;
        pointer-events: none;
    }

    /* ── Inner regions ───────────────────────────────────────────────────── */
    .wcs-card__footer {
        margin-top: 0.75rem;
        padding-top: 0.6rem;
        border-top: 1px solid rgb(var(--gray-200));
    }
    .dark .wcs-card__footer {
        border-color: rgb(var(--gray-700));
    }

</style>
@endonce
