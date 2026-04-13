<x-filament-panels::page>
@includeIf('webkernel::panels.auth.css', [
    'bgLight' => webkernelBrandingUrl('webkernel-bg-login-light'),
    'bgDark'  => webkernelBrandingUrl('webkernel-bg-login-dark'),
])
    @php
        $reqFailing  = collect($requirements)->where('ok', false)->all();
        $reqPassing  = collect($requirements)->where('ok', true)->all();
        $capFailing  = collect($capabilities)->where('ok', false)->all();
        $capPassing  = collect($capabilities)->where('ok', true)->all();
        $allReqOk    = count($reqFailing) === 0;
        $allCapsOk   = count($capFailing) === 0;
    @endphp

    @php
        $states = [
            'installing' => [
                'icon' => 'heroicon-o-arrow-path',
                'iconClass' => 'text-primary-500 animate-spin',
                'title' => 'Installing Webkernel…',
                'subtitle' => 'Environment, database and deployment setup',
                'body' => null,
            ],
            'done' => [
                'icon' => 'heroicon-m-check-circle',
                'iconClass' => 'text-success-500',
                'title' => 'Installation complete',
                'subtitle' => 'Webkernel is ready',
                'body' => null,
            ],
            'error' => [
                'icon' => 'heroicon-m-x-circle',
                'iconClass' => 'text-danger-500',
                'title' => 'Installation failed',
                'subtitle' => null,
                'body' => $this->errorMessage ?? null,
            ],
        ];

        $state = $states[$this->phase] ?? null;
    @endphp

    @if($state)
        <x-filament::fieldset class="wds-card">
            <div style="padding-left:.6rem;display:flex;align-items:flex-start;gap:12px;flex-direction:column;">

                <div style="display:flex;align-items:center;gap:12px;">
                    <x-filament::icon
                        :icon="$state['icon']"
                        class="w-5 h-5 {{ $state['iconClass'] }}"
                    />

                    <div style="display:flex;flex-direction:column;">
                        <p style="font-weight:600;font-size:16px;margin:0;">
                            {{ $state['title'] }}
                        </p>

                        @if($state['subtitle'])
                            <p style="font-size:14px;color:#555;margin:0;">
                                {{ $state['subtitle'] }}
                            </p>
                        @endif
                    </div>
                </div>

                @if($state['body'])
                    <pre style="background:#111;color:#eee;padding:8px;border-radius:4px;font-size:13px;overflow:auto;margin:0;width:100%;">
                            {{ $state['body'] }}
                    </pre>
                @endif

            </div>
        </x-filament::fieldset>

    @else
        <div class="wds-grid">

            {{-- REQUIREMENTS --}}
            <x-filament::fieldset class="wds-card">
                <div class="wds-card-inner">
                    <div class="wds-head">
                        <span>Requirements <sup class="wds-required">*</sup></span>
                        @if($allReqOk)
                            <x-filament::badge color="success">OK</x-filament::badge>
                        @else
                            <x-filament::badge color="danger">{{ count($reqFailing) }} issues</x-filament::badge>
                        @endif
                    </div>
                    <hr class="wds-divider">
                    <div class="wds-body">
                        @if(count($reqFailing))
                            <div class="wds-row">
                                @foreach($reqFailing as $req)
                                    <x-filament::badge color="danger" size="sm">{{ $req['label'] }}</x-filament::badge>
                                @endforeach
                            </div>
                        @endif
                        <div class="wds-row">
                            @foreach($reqPassing as $req)
                                <x-filament::badge color="success" size="sm" outlined>{{ $req['label'] }}</x-filament::badge>
                            @endforeach
                        </div>
                    </div>
                    <div class="wds-foot">
                        <hr class="wds-divider">
                        <span>{{ $allReqOk ? 'All checks passing' : 'Fix required' }}</span>
                    </div>
                </div>
            </x-filament::fieldset>

            {{-- CAPABILITIES --}}
            <x-filament::fieldset class="wds-card">
                <div class="wds-card-inner">
                    <div class="wds-head">
                        <span>Capabilities</span>
                        @if($allCapsOk)
                            <x-filament::badge color="success">Full</x-filament::badge>
                        @else
                            <x-filament::badge color="warning">{{ count($capFailing) }} missing</x-filament::badge>
                        @endif
                    </div>
                    <hr class="wds-divider">
                    <div class="wds-body">
                        @if(count($capFailing))
                            <div class="wds-row">
                                @foreach($capFailing as $cap)
                                    <x-filament::badge color="gray" size="sm">{{ $cap['label'] }}</x-filament::badge>
                                @endforeach
                            </div>
                        @endif
                        <div class="wds-row">
                            @foreach($capPassing as $cap)
                                <x-filament::badge color="success" size="sm" outlined>{{ $cap['label'] }}</x-filament::badge>
                            @endforeach
                        </div>
                    </div>
                    <div class="wds-foot">
                        <hr class="wds-divider">
                        <span>{{ $allCapsOk ? 'All detected' : 'Partial support' }}</span>
                    </div>
                </div>
            </x-filament::fieldset>

        </div>
    @endif

    {{-- THEME SWITCHER & LINKS --}}
        <div style="display: flex; justify-content: space-between; align-items: flex-start; width: 100%;">

            {{-- Left side: Theme Switcher --}}
            <div style="transform: scale(0.8); transform-origin: left;">
                @includeIf('filament-panels::components.theme-switcher.index')
            </div>

            {{-- Right side: Links & Copyright --}}
            <div class="fi-header-subheading" style="display: flex; flex-direction: column; align-items: flex-end; text-align: right; line-height: 1.1;">
                <a href="https://webkernelphp.com" target="_blank" style="text-decoration: none; margin-bottom: 0;">
                    Webkernel
                </a>
                <a href="https://numerimondes.com" target="_blank" style="text-decoration: none; margin-bottom: 0;">
                    Numerimondes
                </a>
            </div>

        </div>

    <style>
        /* --- OVERRIDES ---*/

        .fi-header {
            padding-top: .7rem;
        }

        .fi-page-content {
            row-gap: calc(var(--spacing) * 3);
        }

        .fi-page-header-main-ctn {
            row-gap: calc(var(--spacing) * 9);
        }

        .fi-btn {
            font-weight: unset;
        }
        /* ── LOGO ── */
        .wds-logo-wrap {
            display: flex;
            justify-content: center;
            margin-bottom: .75rem;
        }
        /* Filament gère déjà fi-logo-light/dark via html.dark */
        .fi-logo {
            height: 2rem;
            width: auto;
            display: none;
        }
        html:not(.dark) .fi-logo-light { display: block; }
        html.dark       .fi-logo-dark  { display: block; }

        /* ── PAGE LAYOUT ── */
        :is(.fi-main, .fi-simple-main).fi-width-lg {
            max-width: calc(var(--container-lg) * 1.5);
        }
        .fi-simple-main .fi-width-lg,
        .fi-page-header-main-ctn,
        .fi-simple-main {
            padding: .5rem !important;
        }

        /* ── CENTER (installing) ── */
        .wds-center {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 60px 20px;
            text-align: center;
        }

        /* ── GRID ── */
        .wds-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            align-items: stretch;
        }
        @media (max-width: 768px) {
            .wds-grid { grid-template-columns: 1fr; }
        }

        /* ── CARD ── */
        .wds-card {
            height: 100% !important;
            padding: .6rem !important;
        }
        .wds-card-inner {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        /* ── HEAD ── */
        .fi-header-subheading {
            font-size: 12px;
        }

        .wds-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .02em;
            margin-bottom: 6px;
        }
        .wds-required { color: #ef4444; }

        /* ── DIVIDER ── */
        .wds-divider {
            border: none;
            height: 1px;
            background: color-mix(in oklab, var(--color-white) 10%, transparent);
            margin-bottom: .5rem;
        }

        /* ── BODY ── */
        .wds-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
            overflow: auto;
        }

        /* ── ROW ── */
        .wds-row {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }

        /* ── FOOT ── */
        .wds-foot {
            margin-top: 8px;
            font-size: 11px;
            opacity: .55;
            text-align: right;
        }
        .wds-foot .wds-divider { margin-bottom: .4rem; }

        /* ── TEXT ── */
        .wds-title { font-size: 14px; font-weight: 500; margin: 0; }
        .wds-sub   { font-size: 12px; opacity: .6; margin: 0; }

        /* ── CONSOLE ── */
        .wds-console {
            margin-top: 8px;
            padding: 10px;
            font-size: 11px;
            max-height: 200px;
            overflow: auto;
            background: #0D1117;
            color: #F97583;
            border-radius: 8px;
        }
    </style>
</x-filament-panels::page>
