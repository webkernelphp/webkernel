<x-filament-panels::page>
<style>
 /* Mobile */
:is(.fi-main,.fi-simple-main).fi-width-lg {
  max-width: var(--container-lg);
}
.fi-sc-wizard:not(.fi-sc-wizard-header-hidden) .fi-sc-wizard-step.fi-active {
    margin-top: calc(var(--spacing) * 2);
}

/* Desktop */
@media (min-width: 1024px) {
  .fi-sc-wizard .fi-sc-wizard-header .fi-sc-wizard-header-step .fi-sc-wizard-header-step-btn {
      padding-left: calc(var(--spacing) * 3) !important;
  }

  :is(.fi-main,.fi-simple-main).fi-width-lg {
    min-width: calc(var(--container-lg) * 1.45);
  }
}
</style>
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
                'icon'      => 'heroicon-o-arrow-path',
                'iconClass' => 'text-primary-500 animate-spin',
                'title'     => 'Installing Webkernel…',
                'subtitle'  => 'Environment, database and deployment setup',
                'body'      => null,
            ],
            'error' => [
                'icon'      => 'heroicon-m-x-circle',
                'iconClass' => 'text-danger-500',
                'title'     => 'Installation failed',
                'subtitle'  => null,
                'body'      => $this->errorMessage ?? null,
            ],
        ];

        $state = $states[$this->phase] ?? null;
    @endphp

    {{-- ── INSTALLING / ERROR ──────────────────────────────────────────── --}}
    @if ($state)
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
                        @if ($state['subtitle'])
                            <p style="font-size:14px;color:#555;margin:0;">
                                {{ $state['subtitle'] }}
                            </p>
                        @endif
                    </div>
                </div>

                @if ($state['body'])
                    <pre style="background:#111;color:#eee;padding:8px;border-radius:4px;font-size:13px;overflow:auto;margin:0;width:100%;">{{ $state['body'] }}</pre>
                @endif

            </div>
        </x-filament::fieldset>

    {{-- ── VERIFY TOKEN ─────────────────────────────────────────────────── --}}
    @elseif ($this->phase === 'verify_token')
        @php
            $hasEnvToken = ! empty(config('webkernel.setup_token') ?? env('WEBKERNEL_SETUP_TOKEN'));
            $tokenFile   = storage_path('webkernel/.setup_token');
            $autoToken   = (! $hasEnvToken && file_exists($tokenFile))
                               ? trim(file_get_contents($tokenFile))
                               : null;
        @endphp
        <x-filament::fieldset label="Setup Token">
            <div class="wds-card-inner">
                <div class="wds-head">
                    <span>One-time authentication required</span>
                    <x-filament::badge color="warning">Restricted</x-filament::badge>
                </div>
                <hr class="wds-divider">
                @if ($autoToken)
                    <div style="background:color-mix(in oklab,var(--color-warning-400) 12%,transparent);border:1px solid color-mix(in oklab,var(--color-warning-400) 40%,transparent);border-radius:6px;padding:10px 12px;margin-bottom:12px;">
                        <p style="font-size:11px;font-weight:600;margin:0 0 4px;opacity:.8;">Auto-generated token (one-time use)</p>
                        <code style="font-size:13px;word-break:break-all;">{{ $autoToken }}</code>
                        <p style="font-size:11px;margin:6px 0 0;opacity:.6;">
                            Pre-filled below. Deleted after validation.
                        </p>
                    </div>
                @else
                    <p style="font-size:12px;opacity:.7;margin-bottom:12px;">
                        Enter the <code>WEBKERNEL_SETUP_TOKEN</code> value from your environment.
                    </p>
                @endif
                {{ $this->form }}
            </div>
        </x-filament::fieldset>

    {{-- ── SETUP WIZARD ─────────────────────────────────────────────────── --}}
    @elseif ($this->phase === 'setup')
        {{ $this->form }}

    {{-- ── PRE — requirements + capabilities ──────────────────────────── --}}
    @else

        <div class="wds-grid">

            {{-- REQUIREMENTS --}}
            <x-filament::fieldset class="wds-card">
                <div class="wds-card-inner">
                    <div class="wds-head">
                        <span>Requirements <sup class="wds-required">*</sup></span>
                        @if ($allReqOk)
                            <x-filament::badge color="success">OK</x-filament::badge>
                        @else
                            <x-filament::badge color="danger">{{ count($reqFailing) }} issues</x-filament::badge>
                        @endif
                    </div>
                    <hr class="wds-divider">
                    <div class="wds-body">
                        @if (count($reqFailing))
                            <div class="wds-row">
                                @foreach ($reqFailing as $req)
                                    <x-filament::badge color="danger" size="sm">{{ $req['label'] }}</x-filament::badge>
                                @endforeach
                            </div>
                        @endif
                        <div class="wds-row">
                            @foreach ($reqPassing as $req)
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
                        @if ($allCapsOk)
                            <x-filament::badge color="success">Full</x-filament::badge>
                        @else
                            <x-filament::badge color="warning">{{ count($capFailing) }} missing</x-filament::badge>
                        @endif
                    </div>
                    <hr class="wds-divider">
                    <div class="wds-body">
                        @if (count($capFailing))
                            <div class="wds-row">
                                @foreach ($capFailing as $cap)
                                    <x-filament::badge color="gray" size="sm">{{ $cap['label'] }}</x-filament::badge>
                                @endforeach
                            </div>
                        @endif
                        <div class="wds-row">
                            @foreach ($capPassing as $cap)
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
    <div style="display:flex;justify-content:space-between;align-items:flex-start;width:100%;">
        <div style="transform:scale(0.8);transform-origin:left;">
            @includeIf('filament-panels::components.theme-switcher.index')
        </div>
        <div class="fi-header-subheading" style="display:flex;flex-direction:column;align-items:flex-end;text-align:right;line-height:1.1;">
            <a href="https://webkernelphp.com" target="_blank" style="text-decoration:none;margin-bottom:0;">www.webkernelphp.com</a>
            <a href="https://numerimondes.com"  target="_blank" style="text-decoration:none;margin-bottom:0;">www.numerimondes.com</a>
        </div>
    </div>

    <style>
        .fi-fieldset legend {font-weight:var(--font-weight-normal); font-size: 100%;}
        .fi-header { padding-top:.7rem; }
        .fi-page-content { row-gap:calc(var(--spacing)*3); }
        .fi-page-header-main-ctn { row-gap:calc(var(--spacing)*2); }
        .fi-btn { font-weight:unset; }

        .fi-logo { height:2rem;width:auto;display:none; }
        html:not(.dark) .fi-logo-light { display:block; }
        html.dark       .fi-logo-dark  { display:block; }

        .fi-simple-main .fi-width-lg,
        .fi-page-header-main-ctn,
        .fi-simple-main { padding:.5rem !important; }

        .wds-grid { display:grid;grid-template-columns:1fr 1fr;gap:12px;align-items:stretch; }
        @media (max-width:768px) { .wds-grid { grid-template-columns:1fr; } }

        .wds-card { height:100% !important;padding:.6rem !important; }
        .wds-card-inner { display:flex;flex-direction:column;height:100%; }

        .fi-fo-radio-label-description { font-size:12.5px; }
        .fi-header-subheading { font-size:14px; }

        .wds-head { display:flex;justify-content:space-between;align-items:center;font-size:12px;font-weight:600;letter-spacing:.02em;margin-bottom:6px; }
        .wds-required { color:#ef4444; }
        .wds-divider { border:none;height:1px;background:color-mix(in oklab,var(--color-white) 10%,transparent);margin-bottom:.5rem; }
        .wds-body { flex:1;display:flex;flex-direction:column;gap:6px;overflow:auto; }
        .wds-row { display:flex;flex-wrap:wrap;gap:4px; }
        .wds-foot { margin-top:8px;font-size:11px;opacity:.55;text-align:right; }
        .wds-foot .wds-divider { margin-bottom:.4rem; }

        /* ── Wizard submit button ─────────────────────────────────────────── */
        .fi-sc-wizard-footer button[wire\:click="runCompleteSetup"] {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: 8px;
            background: var(--color-success-600);
            color: #fff;
            font-size: 13px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background .15s;
        }
        .fi-sc-wizard-footer button[wire\:click="runCompleteSetup"]:hover {
            background: var(--color-success-500);
        }
        .fi-sc-wizard-footer button[wire\:click="runCompleteSetup"]:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        /* ── Claim role radio cards ───────────────────────────────────────── */
        .wds-claim-radio .fi-fo-radio-option {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            border: 1.5px solid color-mix(in oklab, currentColor 15%, transparent);
            border-radius: 8px;
            padding: 12px 14px;
            cursor: pointer;
            transition: border-color .15s, background .15s;
            margin-bottom: 8px;
        }
        .wds-claim-radio .fi-fo-radio-option:last-child { margin-bottom: 0; }
        .wds-claim-radio .fi-fo-radio-option:hover {
            border-color: color-mix(in oklab, var(--color-primary-400) 60%, transparent);
            background: color-mix(in oklab, var(--color-primary-400) 5%, transparent);
        }
        .wds-claim-radio .fi-fo-radio-option:has(input:checked) {
            border-color: var(--color-primary-500);
            background: color-mix(in oklab, var(--color-primary-500) 8%, transparent);
        }
        .wds-claim-radio .fi-fo-radio-option:has(input:checked) .fi-fo-radio-option-label {
            color: var(--color-primary-400);
            font-weight: 600;
        }
        .wds-claim-radio .fi-fo-radio-option-label { font-size: 13px; font-weight: 500; }
        .wds-claim-radio .fi-fo-radio-option-description { font-size: 11px; opacity: .65; margin-top: 2px; }
        .wds-claim-radio input[type="radio"] { margin-top: 2px; flex-shrink: 0; accent-color: var(--color-primary-500); }
    </style>
</x-filament-panels::page>
