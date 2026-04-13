<x-filament-panels::page>
@includeIf('webkernel::panels.auth.css', [
    'bgLight' => webkernelBrandingUrl('bg-login-light'),
    'bgDark'  => webkernelBrandingUrl('bg-login-dark'),
])
    @php
        $reqFailing  = collect($requirements)->where('ok', false)->all();
        $reqPassing  = collect($requirements)->where('ok', true)->all();
        $capFailing  = collect($capabilities)->where('ok', false)->all();
        $capPassing  = collect($capabilities)->where('ok', true)->all();
        $allReqOk    = count($reqFailing) === 0;
        $allCapsOk   = count($capFailing) === 0;
    @endphp


    @if($this->phase === 'installing')
        <div class="wds-center">
            <x-filament::loading-indicator class="w-8 h-8"/>
            <div>
                <p class="wds-title">Installing Webkernel…</p>
                <p class="wds-sub">Environment, database and deployment setup</p>
            </div>
        </div>

    @elseif($this->phase === 'done')
        <x-filament::fieldset>
            <div class="flex items-center gap-3">
                <x-filament::icon icon="heroicon-m-check-circle" class="w-5 h-5 text-success-500"/>
                <div>
                    <p class="wds-title">Installation complete</p>
                    <p class="wds-sub">Webkernel is ready</p>
                </div>
            </div>
        </x-filament::fieldset>

    @elseif($this->phase === 'error')
        <x-filament::fieldset>
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-m-x-circle" class="w-5 h-5 text-danger-500"/>
                <p class="wds-title">Installation failed</p>
            </div>
        </x-filament::fieldset>
        <pre class="wds-console">{{ $this->errorMessage }}</pre>

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

    {{-- THEME SWITCHER --}}
    <div style="margin-top: 0rem; padding: 0 1rem; max-width: 384px; margin-inline: auto;">
        @includeIf('filament-panels::components.theme-switcher.index')
    </div>

    <style>
        /* ── HIDE FILAMENT HEADING ──
        .fi-header-heading,
        .fi-header-subheading { display: none !important; }
        */
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
