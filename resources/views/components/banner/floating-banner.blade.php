@php
    use Filament\Support\Enums\IconSize;
    use Filament\Support\View\Components\CalloutComponent\IconComponent;
    use function Filament\Support\generate_icon_html;

    $iconMap = [
        'danger'  => 'heroicon-o-x-circle',
        'warning' => 'heroicon-o-exclamation-triangle',
        'info'    => 'heroicon-o-information-circle',
        'success' => 'heroicon-o-check-circle',
    ];
@endphp

<style>
    [x-cloak] {
        display: none !important;
    }

    :root {
        --wk-banner-height: 0px;
    }

    body.fi-body {
        height: calc(100dvh - var(--wk-banner-height)) !important;
        min-height: calc(100dvh - var(--wk-banner-height)) !important;
        overflow: hidden !important;
    }

    .fi-main,
    .fi-layout,
    main {
        height: 100% !important;
        min-height: 100% !important;
        overflow: auto;
    }

    .wk-banner-stack {
        display: block;
        width: 100%;
    }

    .wk-banner-stack .fi-callout {
        margin: 0 !important;
        border-radius: 0 !important;
    }
</style>

<div
    x-data="{
        storageKey: 'wkt-banners-dismissed',

        measure() {
            this.$nextTick(() => {
                document.documentElement.style.setProperty(
                    '--wk-banner-height',
                    this.$el.offsetHeight + 'px'
                );
            });
        },

        init() {
            this.measure();

            window.addEventListener(
                'resize',
                () => this.measure()
            );
        }
    }"
    x-init="init()"
    class="wk-banner-stack"
>
    @foreach ($banners as $banner)

        @php
            $color = $banner['type'] ?? 'info';
            $icon = $iconMap[$color] ?? 'heroicon-o-information-circle';
            $id = $banner['id'];

            $heading = $banner['title'] ?? null;
            $description = $banner['message'] ?? null;

            $hasHeading = filled($heading);
            $hasDescription = filled($description);

            $hasAction =
                filled($banner['action_label'] ?? null) &&
                filled($banner['action_url'] ?? null);

            $isCloseable = $banner['closeable'] ?? false;

            $hasControls = $hasAction || $isCloseable;
        @endphp

        <div
            x-data="{
                visible: true,

                init() {
                    const closed = JSON.parse(
                        localStorage.getItem(storageKey) || '[]'
                    );

                    if (closed.includes('{{ $id }}')) {
                        this.visible = false;
                    }

                    $nextTick(
                        () => $root.measure()
                    );
                },

                dismiss() {
                    const closed = JSON.parse(
                        localStorage.getItem(storageKey) || '[]'
                    );

                    if (! closed.includes('{{ $id }}')) {
                        closed.push('{{ $id }}');
                    }

                    localStorage.setItem(
                        storageKey,
                        JSON.stringify(closed)
                    );

                    this.visible = false;

                    $nextTick(
                        () => $root.measure()
                    );
                }
            }"

            x-show="visible"
            x-cloak

            {{
                (new \Illuminate\View\ComponentAttributeBag)
                    ->color(
                        \Filament\Support\View\Components\CalloutComponent::class,
                        $color
                    )
                    ->class(['fi-callout'])
                    ->style(['padding: calc(var(--spacing) * 2)'])
            }}
        >
            {{
                generate_icon_html(
                    $icon,
                    attributes:
                        (new \Illuminate\View\ComponentAttributeBag)
                            ->color(
                                IconComponent::class,
                                $color
                            )
                            ->class([
                                'fi-callout-icon',
                            ]),
                    size: IconSize::Large,
                )
            }}

            @if ($hasHeading || $hasDescription)
                <div
                    class="fi-callout-main"
                    style="
                        display:flex;
                        align-items:baseline;
                        gap:6px;
                        flex-wrap:nowrap;
                    "
                >
                    <div
                        class="fi-callout-text"
                        style="
                            display:flex;
                            align-items:baseline;
                            gap:6px;
                            flex-wrap:nowrap;
                        "
                    >
                        @if ($hasHeading)
                            <h4
                                class="fi-callout-heading"
                                style="margin:0;"
                            >
                                {{ $heading }}
                            </h4>
                        @endif

                        @if ($hasDescription)
                            <p
                                class="fi-callout-description"
                                style="margin:0;"
                            >
                                {{ $description }}
                            </p>
                        @endif
                    </div>
                </div>
            @endif

            @if ($hasControls)
                <div class="fi-callout-controls">
                    @if ($hasAction)
                        <x-filament::button
                            href="{{ $banner['action_url'] }}"
                            color="{{ $color }}"
                            size="{{ $banner['action_size'] ?? 'xs' }}"
                        >
                            {{ $banner['action_label'] }}
                        </x-filament::button>
                    @endif

                    @if ($isCloseable)
                        <button
                            type="button"
                            x-on:click="dismiss()"
                            title="Dismiss"
                        >
                            <x-filament::icon
                                icon="heroicon-o-x-mark"
                                class="h-4 w-4"
                            />
                        </button>
                    @endif
                </div>
            @endif

        </div>

    @endforeach
</div>
