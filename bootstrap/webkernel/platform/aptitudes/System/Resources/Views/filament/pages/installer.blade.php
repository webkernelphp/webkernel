<x-filament-panels::page>

    @php
        $reqFailing  = collect($requirements)->where('ok', false)->all();
        $reqPassing  = collect($requirements)->where('ok', true)->all();
        $capFailing  = collect($capabilities)->where('ok', false)->all();
        $capPassing  = collect($capabilities)->where('ok', true)->all();
        $allReqOk    = count($reqFailing) === 0;
        $allCapsOk   = count($capFailing) === 0;
        $allOk       = $allReqOk && $allCapsOk;
    @endphp

    {{-- ══════════════════════════════════════════════════════════════════════
         PHASE: installing
    ══════════════════════════════════════════════════════════════════════ --}}

    @if($this->phase === 'installing')

        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:24px;padding:80px 24px;text-align:center;">
            <svg viewBox="0 0 44 44" fill="none" style="width:44px;height:44px;animation:webkernel-spin .85s linear infinite;">
                <circle cx="22" cy="22" r="18" stroke="currentColor" stroke-width="2" style="opacity:.1"/>
                <path d="M22 4A18 18 0 0 1 40 22" stroke="rgb(var(--primary-500))" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <div>
                <p style="font-size:15px;font-weight:500;margin:0 0 6px;">Installing Webkernel…</p>
                <p style="font-size:13px;margin:0;opacity:.5;line-height:1.6;">Setting up environment, database, and deployment profile.</p>
            </div>
            <div style="display:flex;gap:4px;">
                @foreach([0,1,2,3,4] as $i)
                    <div style="width:5px;height:5px;border-radius:50%;background:rgb(var(--primary-400));animation:webkernel-pulse 1.4s ease-in-out {{ $i*.18 }}s infinite;"></div>
                @endforeach
            </div>
        </div>

        <style>
            @keyframes webkernel-spin  { to { transform:rotate(360deg); } }
            @keyframes webkernel-pulse { 0%,80%,100%{opacity:.15;transform:scale(.75)} 40%{opacity:1;transform:scale(1)} }
        </style>

    {{-- ══════════════════════════════════════════════════════════════════════
         PHASE: done
    ══════════════════════════════════════════════════════════════════════ --}}
    @elseif($this->phase === 'done')

        <div style="display:flex;flex-direction:column;gap:10px;">

            <div style="border-radius:12px;padding:18px 20px;background:rgb(var(--success-50));border:1px solid rgb(var(--success-200));display:flex;gap:14px;align-items:flex-start;"
                 class="dark:border-success-800 dark:bg-success-950/30">
                <div style="width:32px;height:32px;border-radius:50%;background:rgb(var(--success-100));border:1px solid rgb(var(--success-200));flex-shrink:0;display:flex;align-items:center;justify-content:center;"
                     class="dark:bg-success-900/50 dark:border-success-700">
                    <x-filament::icon icon="heroicon-m-check" style="width:16px;height:16px;color:rgb(var(--success-600));" class="dark:text-success-400"/>
                </div>
                <div>
                    <p style="font-size:14px;font-weight:500;margin:0 0 3px;color:rgb(var(--success-800));" class="dark:text-success-300">Installation complete</p>
                    <p style="font-size:12px;margin:0;opacity:.7;color:rgb(var(--success-800));line-height:1.6;" class="dark:text-success-400">Webkernel is ready. Click <strong>Open Webkernel</strong> in the header.</p>
                </div>
            </div>

            @if($this->artisanOutput)
                <div style="border-radius:10px;overflow:hidden;border:1px solid rgba(0,0,0,.1);" class="dark:border-white/10">
                    <div style="padding:8px 14px;display:flex;align-items:center;gap:6px;background:rgba(0,0,0,.03);border-bottom:1px solid rgba(0,0,0,.06);" class="dark:bg-white/4 dark:border-white/8">
                        <div style="display:flex;gap:4px;"><div style="width:9px;height:9px;border-radius:50%;background:#FF5F57;"></div><div style="width:9px;height:9px;border-radius:50%;background:#FEBC2E;"></div><div style="width:9px;height:9px;border-radius:50%;background:#28C840;"></div></div>
                        <span style="font-size:10px;opacity:.4;font-family:monospace;">artisan output</span>
                    </div>
                    <pre style="margin:0;padding:14px 16px;font-size:11.5px;line-height:1.75;background:#0D1117;color:#7EE787;overflow-x:auto;max-height:220px;white-space:pre-wrap;word-break:break-all;">{{ $this->artisanOutput }}</pre>
                </div>
            @endif

        </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         PHASE: error
    ══════════════════════════════════════════════════════════════════════ --}}
    @elseif($this->phase === 'error')
        <div style="display:flex;flex-direction:column;gap:10px;">

            <div style="border-radius:8px;padding:10px 12px;background:rgb(var(--danger-50));border:1px solid rgb(var(--danger-200));display:flex;gap:10px;align-items:center;"
                 class="dark:border-danger-800 dark:bg-danger-950/30">
                <x-filament::icon icon="heroicon-m-x-mark" style="width:14px;height:14px;color:rgb(var(--danger-600));" class="dark:text-danger-400"/>
                <p style="font-size:13px;font-weight:500;margin:0;color:rgb(var(--danger-800));" class="dark:text-danger-300">
                    Installation failed
                </p>
            </div>

            <div style="border-radius:6px;overflow:hidden;border:1px solid rgba(0,0,0,.15);" class="dark:border-white/10">
                <div style="padding:6px 10px;display:flex;align-items:center;gap:6px;background:rgba(0,0,0,.03);border-bottom:1px solid rgba(0,0,0,.06);" class="dark:bg-white/4 dark:border-white/8">
                    <div style="display:flex;gap:4px;">
                        <div style="width:9px;height:9px;border-radius:50%;background:#FF5F57;"></div>
                        <div style="width:9px;height:9px;border-radius:50%;background:#FEBC2E;"></div>
                        <div style="width:9px;height:9px;border-radius:50%;background:#28C840;"></div>
                    </div>
                    <span style="font-size:10px;opacity:.5;font-family:monospace;">exception</span>
                </div>
                <pre style="margin:0;padding:10px 12px;font-size:12px;line-height:1.5;background:#0D1117;color:#F97583;overflow-x:auto;max-height:240px;white-space:pre-wrap;word-break:break-word;">
    {{ $this->errorMessage }}
                </pre>
            </div>

        </div>


    {{-- ══════════════════════════════════════════════════════════════════════
         PHASE: pre  —  THE MAIN DASHBOARD
    ══════════════════════════════════════════════════════════════════════ --}}
    @else

        {{-- ────────────────────────────────────────────────────────────────
             TOP ROW: Requirements card + Capabilities card side by side.
             Each card shrinks to a compact summary pill when fully OK,
             and expands only to show the items that need attention.
        ─────────────────────────────────────────────────────────────────── --}}
        <div class="webkernel-top-row" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">

            {{-- ── SYSTEM REQUIREMENTS ──────────────────────────────────── --}}
            @if($allReqOk)
                {{-- ALL CLEAR: compact pastel card --}}
                <div style="border-radius:12px;padding:14px 18px;background:rgb(var(--success-50));border:1px solid rgb(var(--success-200));display:flex;align-items:center;gap:12px;"
                     class="dark:border-success-800 dark:bg-success-950/30">
                    <div style="width:28px;height:28px;border-radius:50%;background:rgb(var(--success-100));border:1px solid rgb(var(--success-200));flex-shrink:0;display:flex;align-items:center;justify-content:center;"
                         class="dark:bg-success-900/60 dark:border-success-700">
                        <x-filament::icon icon="heroicon-m-check" style="width:14px;height:14px;color:rgb(var(--success-600));" class="dark:text-success-400"/>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <p style="font-size:13px;font-weight:500;margin:0 0 1px;color:rgb(var(--success-800));" class="dark:text-success-300">System requirements</p>
                        <p style="font-size:11px;margin:0;color:rgb(var(--success-700));opacity:.7;" class="dark:text-success-400">All {{ count($requirements) }} checks passing</p>
                    </div>
                    <span style="font-size:11px;font-weight:500;padding:3px 10px;border-radius:20px;background:rgb(var(--success-100));color:rgb(var(--success-700));border:1px solid rgb(var(--success-200));white-space:nowrap;"
                          class="dark:bg-success-900/50 dark:border-success-700 dark:text-success-400">All clear</span>
                </div>

            @else
                {{-- HAS FAILURES: expanded card, failures prominent, passing quiet --}}
                <div style="border-radius:12px;border:1px solid rgb(var(--danger-200));overflow:hidden;" class="dark:border-danger-800">

                    <div style="padding:13px 16px;background:rgb(var(--danger-50));border-bottom:1px solid rgb(var(--danger-200));display:flex;align-items:center;justify-content:space-between;gap:8px;"
                         class="dark:border-danger-800 dark:bg-danger-950/40">
                        <div>
                            <p style="font-size:13px;font-weight:500;margin:0 0 1px;color:rgb(var(--danger-800));" class="dark:text-danger-300">System requirements</p>
                            <p style="font-size:11px;margin:0;color:rgb(var(--danger-700));opacity:.75;" class="dark:text-danger-400">{{ count($reqFailing) }} of {{ count($requirements) }} failing</p>
                        </div>
                        <span style="font-size:11px;font-weight:500;padding:3px 9px;border-radius:20px;background:rgb(var(--danger-100));color:rgb(var(--danger-700));border:1px solid rgb(var(--danger-200));white-space:nowrap;"
                              class="dark:bg-danger-900/50 dark:border-danger-700 dark:text-danger-400">Action needed</span>
                    </div>

                    <div style="padding:8px 14px 10px;">
                        {{-- Failures first --}}
                        @foreach($reqFailing as $req)
                            <div style="display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid rgba(0,0,0,.05);" class="dark:border-white/6">
                                <x-filament::icon icon="heroicon-m-x-mark" style="width:14px;height:14px;flex-shrink:0;color:rgb(var(--danger-500));" class="dark:text-danger-400"/>
                                <span style="font-size:12px;font-weight:500;color:rgb(var(--danger-800));" class="dark:text-danger-300">{{ $req['label'] }}</span>
                                @if($req['value'])
                                    <span style="font-size:10px;font-family:monospace;opacity:.5;margin-left:auto;">{{ $req['value'] }}</span>
                                @endif
                            </div>
                        @endforeach
                        {{-- Passing, compact --}}
                        @foreach($reqPassing as $req)
                            <div style="display:flex;align-items:center;gap:8px;padding:5px 0;">
                                <x-filament::icon icon="heroicon-m-check" style="width:12px;height:12px;flex-shrink:0;color:rgb(var(--success-500));opacity:.7;" class="dark:text-success-400"/>
                                <span style="font-size:12px;opacity:.45;">{{ $req['label'] }}</span>
                                @if($req['value'])
                                    <span style="font-size:10px;font-family:monospace;opacity:.3;margin-left:auto;">{{ $req['value'] }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>

                </div>
            @endif

            {{-- ── HOST CAPABILITIES ────────────────────────────────────── --}}
            @if($allCapsOk)
                {{-- ALL PRESENT: compact pastel card --}}
                <div style="border-radius:12px;padding:14px 18px;background:#EEF2FF;border:1px solid #C7D2FE;display:flex;align-items:center;gap:12px;"
                     class="dark:border-indigo-800/60 dark:bg-indigo-950/30">
                    <div style="width:28px;height:28px;border-radius:50%;background:#E0E7FF;border:1px solid #C7D2FE;flex-shrink:0;display:flex;align-items:center;justify-content:center;"
                         class="dark:bg-indigo-900/60 dark:border-indigo-700">
                        <x-filament::icon icon="heroicon-m-server-stack" style="width:14px;height:14px;color:#4F46E5;" class="dark:text-indigo-400"/>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <p style="font-size:13px;font-weight:500;margin:0 0 1px;color:#3730A3;" class="dark:text-indigo-300">Host capabilities</p>
                        <p style="font-size:11px;margin:0;color:#4338CA;opacity:.7;" class="dark:text-indigo-400">All {{ count($capabilities) }} detected · <strong>{{ $hostname }}</strong> · <code style="font-size:10px;font-family:monospace;background:#C7D2FE;padding:0 4px;border-radius:3px;" class="dark:bg-indigo-800/60">{{ $profile }}</code></p>
                    </div>
                    <span style="font-size:11px;font-weight:500;padding:3px 10px;border-radius:20px;background:#E0E7FF;color:#4338CA;border:1px solid #C7D2FE;white-space:nowrap;"
                          class="dark:bg-indigo-900/50 dark:border-indigo-700 dark:text-indigo-400">Full coverage</span>
                </div>

            @else
                {{-- PARTIAL: expanded card, missing items shown, present quiet --}}
                <div style="border-radius:12px;border:1px solid rgba(0,0,0,.08);overflow:hidden;" class="dark:border-white/10">

                    <div style="padding:13px 16px;border-bottom:1px solid rgba(0,0,0,.06);display:flex;align-items:center;justify-content:space-between;gap:8px;" class="dark:border-white/8">
                        <div>
                            <p style="font-size:13px;font-weight:500;margin:0 0 1px;">Host capabilities</p>
                            <p style="font-size:11px;margin:0;opacity:.5;">{{ count($capPassing) }}/{{ count($capabilities) }} · <strong>{{ $hostname }}</strong> · <code style="font-size:10px;font-family:monospace;opacity:.7;">{{ $profile }}</code></p>
                        </div>
                        <span style="font-size:11px;font-weight:500;padding:3px 9px;border-radius:20px;background:rgba(0,0,0,.04);border:1px solid rgba(0,0,0,.08);opacity:.6;white-space:nowrap;" class="dark:bg-white/6 dark:border-white/10">Partial</span>
                    </div>

                    <div style="padding:8px 14px 10px;">
                        {{-- Missing first --}}
                        @foreach($capFailing as $cap)
                            <div style="display:flex;align-items:flex-start;gap:8px;padding:8px 0;border-bottom:1px solid rgba(0,0,0,.05);" class="dark:border-white/6">
                                <x-filament::icon icon="heroicon-m-minus" style="width:14px;height:14px;flex-shrink:0;margin-top:1px;opacity:.35;"/>
                                <div>
                                    <p style="font-size:12px;font-weight:500;margin:0;opacity:.55;">{{ $cap['label'] }}</p>
                                    <p style="font-size:11px;margin:2px 0 0;opacity:.35;line-height:1.4;">{{ $cap['help'] }}</p>
                                </div>
                            </div>
                        @endforeach
                        {{-- Present, compact --}}
                        @foreach($capPassing as $cap)
                            <div style="display:flex;align-items:center;gap:8px;padding:5px 0;">
                                <x-filament::icon icon="heroicon-m-check" style="width:12px;height:12px;flex-shrink:0;color:rgb(var(--success-500));opacity:.7;" class="dark:text-success-400"/>
                                <span style="font-size:12px;opacity:.45;">{{ $cap['label'] }}</span>
                            </div>
                        @endforeach
                        <p style="font-size:11px;margin:8px 0 0;opacity:.3;line-height:1.5;">Missing capabilities reduce metric coverage — not blocking.</p>
                    </div>

                </div>
            @endif

        </div>{{-- /webkernel-top-row --}}



    @endif

    <style>
        @keyframes webkernel-spin  { to { transform:rotate(360deg); } }
        @keyframes webkernel-pulse { 0%,80%,100%{opacity:.15;transform:scale(.75)} 40%{opacity:1;transform:scale(1)} }
        @media (max-width:768px) { .webkernel-top-row { grid-template-columns:1fr !important; } }
    </style>

</x-filament-panels::page>
