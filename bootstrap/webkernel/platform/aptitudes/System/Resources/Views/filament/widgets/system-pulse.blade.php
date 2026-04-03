{{--
    system-pulse.blade.php
    Zero $data. Zero trait. All via webkernel()->. Octane/Swoole safe.
    Card: x-webkernel::card compact  with slots: leading, header, $slot, meta, footer
    Props: :tone="emerald|amber|red|blue|gray"  :disabled  :interactive
--}}
@php
    $css  = fn(float $pct, bool $inv = false): string => \Webkernel\System\Support\MetricColor::css($pct, $inv);
    $logW = fn(float $pct): float => \Webkernel\System\Support\LogScale::apply($pct);

    // FFI mode: 'all' | 'preload' | 'disabled'
    $ffiMode = webkernel()->os()->ffiMode();
    $ffiLabel = match ($ffiMode) {
        'all'     => 'FFI / all',
        'preload' => 'FFI / preload',
        default   => '/proc fallback',
    };
    $ffiBadgeColor = match ($ffiMode) {
        'all'     => 'success',
        'preload' => 'warning',
        default   => 'gray',
    };
    $degraded = $ffiMode !== 'all';

    $fpm    = webkernel()->host()->fpm();
    $hasFpm = $fpm->available();

    $opc        = webkernel()->instance()->opcache();
    $opcEnabled = $opc->enabled();
    $opcHit     = (float) ($opc->hitRatio() ?? 0.0);
    $opcWasted  = (float) ($opc->wastedPercentage() ?? 0.0);

    $sysRam  = webkernel()->host()->memory();
    $hasSwap = $sysRam->hasSwap();
    $swapPct = $hasSwap ? $sysRam->swapPercentage() : 0.0;

    // /proc availability: RAM total > 0 is a reliable proxy (returns 0 when /proc is blocked)
    $procAvailable = $sysRam->total() > 0;

    $phpIniSnippet = implode("\n", [
        '; Enable FFI extension',
        'extension=ffi',
        '',
        '; ffi.enable=true    -> full runtime FFI (required for dynamic metric reads)',
        '; ffi.enable=preload -> only preloaded defs — NOT sufficient for runtime reads',
        'ffi.enable=true',
        '',
        '; Restart after saving:  systemctl restart php8.x-fpm',
    ]);
@endphp

<div
    wire:poll.5000ms="$refresh"
    x-data="{
        now: '',
        elapsed: 0,
        _t: null,
        init() {
            const tick = () => {
                const d = new Date();
                this.now = d.toLocaleTimeString('en-GB', { hour:'2-digit', minute:'2-digit', second:'2-digit', hour12:false });
                this.elapsed = (this.elapsed + 1) % 5;
            };
            tick();
            this._t = setInterval(tick, 1000);
        },
        destroy() { clearInterval(this._t); }
    }"
    style="display:flex;flex-direction:column;gap:0.5rem;"
>

    {{-- STATUS BAR --}}
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.4rem;padding:0 0.1rem;">
        <div style="display:inline-flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
            <span style="position:relative;display:inline-flex;width:8px;height:8px;flex-shrink:0;">
                <span style="position:absolute;inset:0;border-radius:9999px;background:rgb(16,185,129);opacity:0.7;animation:wk-ping 1.4s cubic-bezier(0,0,.2,1) infinite;"></span>
                <span style="position:relative;width:8px;height:8px;border-radius:9999px;background:rgb(16,185,129);"></span>
            </span>
            <span style="font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:rgb(var(--gray-400));font-size:.72rem;">LIVE</span>
            <x-filament::badge :color="$ffiBadgeColor" >{{ $ffiLabel }}</x-filament::badge>
            <x-filament::badge color="gray" >PHP {{ webkernel()->instance()->php()->version() }}</x-filament::badge>
            <x-filament::badge color="gray" >{{ strtoupper(webkernel()->runtime()->sapi()->value) }}</x-filament::badge>
            <x-filament::badge color="gray" >{{ webkernel()->os()->family() }}</x-filament::badge>
            @if ($procAvailable)
                <x-filament::badge color="gray" >up {{ webkernel()->host()->uptime()->human() }}</x-filament::badge>
            @endif
        </div>
        <div style="display:inline-flex;align-items:center;gap:.35rem;font-variant-numeric:tabular-nums;color:rgb(var(--gray-500));font-size:.8rem;">
            <x-filament::icon icon="heroicon-m-clock" style="width:.875rem;height:.875rem;color:rgb(var(--gray-400));flex-shrink:0;" />
            <span x-text="now" style="min-width:5.5ch;"></span>
        </div>
    </div>

    {{-- THIS INSTANCE --}}
    <div style="font-weight:800;letter-spacing:.16em;text-transform:uppercase;color:rgb(var(--gray-400));padding:0 .1rem;font-size:.68rem;">THIS INSTANCE</div>

    <div class="wk-primary-grid">

        {{-- PHP Memory --}}
        @php
            $memPct   = webkernel()->instance()->memory()->percentage();
            $memTone  = $memPct > 90 ? 'red' : ($memPct > 75 ? 'amber' : 'emerald');
            $memColor = $css($memPct);
            $memWidth = $logW($memPct);
        @endphp
        <x-webkernel::card compact  :tone="$memTone">
            <x-slot name="leading">
                <x-filament::icon icon="heroicon-o-circle-stack" style="width:1.25rem;height:1.25rem;color:{{ $memColor }};" />
            </x-slot>
            <x-slot name="header">
                <span style="font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgb(var(--gray-400));">PHP Memory</span>
            </x-slot>

            <div style="height:3px;background:rgb(var(--gray-200));border-radius:9999px;overflow:hidden;margin:.15rem 0;">
                <div style="height:100%;width:{{ $memWidth }}%;background:{{ $memColor }};border-radius:9999px;transition:width .65s cubic-bezier(.4,0,.2,1);"></div>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:.78rem;font-variant-numeric:tabular-nums;">
                <span style="font-weight:700;color:{{ $memColor }};">{{ webkernel()->instance()->memory()->humanUsed() }}</span>
                <span style="color:rgb(var(--gray-400));">/ {{ webkernel()->instance()->memory()->humanLimit() }}</span>
            </div>

            <x-slot name="meta">
                <span style="font-weight:700;font-variant-numeric:tabular-nums;color:{{ $memColor }};font-size:1.1rem;">{{ number_format($memPct,1) }}%</span>
                <span style="font-size:.7rem;color:rgb(var(--gray-400));">peak {{ webkernel()->instance()->memory()->humanPeak() }}</span>
                @if (webkernel()->instance()->memory()->isUnlimited())
                    <x-filament::badge color="gray" >unlimited</x-filament::badge>
                @endif
            </x-slot>
        </x-webkernel::card compact >

        {{-- OPcache --}}
        @php
            $opcTone  = ! $opcEnabled ? 'gray' : ($opcHit > 90 ? 'emerald' : ($opcHit > 70 ? 'amber' : 'red'));
            $opcColor = $css($opcEnabled ? (100 - $opcHit) : 100);
            $opcWidth = $logW($opcEnabled ? $opcHit : 0.0);
        @endphp
        <x-webkernel::card compact  :tone="$opcTone">
            <x-slot name="leading">
                <x-filament::icon icon="heroicon-o-bolt" style="width:1.25rem;height:1.25rem;color:{{ $opcColor }};" />
            </x-slot>
            <x-slot name="header">
                <span style="font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgb(var(--gray-400));">OPcache</span>
            </x-slot>

            @if ($opcEnabled)
                <div style="height:3px;background:rgb(var(--gray-200));border-radius:9999px;overflow:hidden;margin:.15rem 0;">
                    <div style="height:100%;width:{{ $opcWidth }}%;background:{{ $opcColor }};border-radius:9999px;transition:width .65s cubic-bezier(.4,0,.2,1);"></div>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:.78rem;font-variant-numeric:tabular-nums;">
                    <span style="font-weight:700;color:{{ $opcColor }};">{{ number_format($opcHit,1) }}% hit</span>
                    @if ($opc->cachedScripts() !== null)
                        <span style="color:rgb(var(--gray-400));">{{ $opc->cachedScripts() }} scripts</span>
                    @endif
                </div>
            @else
                <x-filament::badge color="gray"  style="margin-top:.25rem;">disabled</x-filament::badge>
            @endif

            <x-slot name="meta">
                @if ($opcEnabled)
                    <span style="font-weight:700;font-variant-numeric:tabular-nums;color:{{ $opcColor }};font-size:1.1rem;">{{ number_format($opcHit,1) }}%</span>
                    @if ($opc->humanMemoryUsed() !== null)
                        <span style="font-size:.7rem;color:rgb(var(--gray-400));">{{ $opc->humanMemoryUsed() }} used</span>
                    @endif
                    @if ($opc->humanMemoryFree() !== null)
                        <span style="font-size:.7rem;color:rgb(var(--gray-400));">{{ $opc->humanMemoryFree() }} free</span>
                    @endif
                    @if ($opcWasted > 5)
                        <x-filament::badge color="warning" >{{ number_format($opcWasted,1) }}% wasted</x-filament::badge>
                    @endif
                @else
                    <x-filament::badge color="gray" >off</x-filament::badge>
                @endif
            </x-slot>
        </x-webkernel::card compact >

        {{-- PHP Limits --}}
        <x-webkernel::card compact >
            <x-slot name="leading">
                <x-filament::icon icon="heroicon-o-adjustments-horizontal" style="width:1.25rem;height:1.25rem;color:rgb(var(--gray-400));" />
            </x-slot>
            <x-slot name="header">
                <span style="font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgb(var(--gray-400));">PHP Limits</span>
            </x-slot>

            <div class="wk-info-table">
                <div class="wk-info-row"><span>max_execution_time</span><x-filament::badge color="gray" >{{ webkernel()->instance()->limits()->humanMaxExecutionTime() }}</x-filament::badge></div>
                <div class="wk-info-row"><span>upload_max_filesize</span><x-filament::badge color="gray" >{{ webkernel()->instance()->limits()->humanUploadMaxFilesize() }}</x-filament::badge></div>
                <div class="wk-info-row"><span>post_max_size</span><x-filament::badge color="gray" >{{ webkernel()->instance()->limits()->humanPostMaxSize() }}</x-filament::badge></div>
                <div class="wk-info-row"><span>max_input_vars</span><x-filament::badge color="gray" >{{ webkernel()->instance()->limits()->maxInputVars() }}</x-filament::badge></div>
            </div>
        </x-webkernel::card compact >

    </div>

    {{-- SYSTEM CONTEXT --}}
    <div style="font-weight:800;letter-spacing:.16em;text-transform:uppercase;color:rgb(var(--gray-400));padding:0 .1rem;margin-top:.25rem;font-size:.68rem;">SYSTEM CONTEXT</div>

    <div class="wk-context-grid">

        {{-- CPU --}}
        @php
            $cpuPct   = $procAvailable ? webkernel()->host()->cpu()->usage() : 0.0;
            $cpuTone  = $procAvailable ? ($cpuPct > 90 ? 'red' : ($cpuPct > 75 ? 'amber' : 'emerald')) : 'gray';
            $cpuColor = $procAvailable ? $css($cpuPct) : 'rgb(var(--gray-400))';
            $cpuWidth = $procAvailable ? $logW($cpuPct) : 0.0;
        @endphp
        <x-webkernel::card compact  :tone="$cpuTone" :disabled="!$procAvailable">
            <x-slot name="header">
                <span style="font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgb(var(--gray-400));">CPU</span>
            </x-slot>

            @if ($procAvailable)
                <div style="height:3px;background:rgb(var(--gray-200));border-radius:9999px;overflow:hidden;margin:.15rem 0;">
                    <div style="height:100%;width:{{ $cpuWidth }}%;background:{{ $cpuColor }};border-radius:9999px;transition:width .65s cubic-bezier(.4,0,.2,1);"></div>
                </div>
                <div style="font-size:.72rem;color:rgb(var(--gray-400));font-variant-numeric:tabular-nums;">
                    load {{ webkernel()->host()->cpu()->loadAvg1() }} / {{ webkernel()->host()->cpu()->loadAvg5() }} / {{ webkernel()->host()->cpu()->loadAvg15() }}
                    &middot; {{ webkernel()->host()->cpu()->cores() }} cores
                </div>
            @else
                <x-filament::badge color="gray" style="margin-top:.25rem;">unavailable</x-filament::badge>
            @endif

            <x-slot name="meta">
                @if ($procAvailable)
                    <span style="font-weight:700;font-variant-numeric:tabular-nums;color:{{ $cpuColor }};font-size:1.1rem;">{{ number_format($cpuPct,1) }}%</span>
                @else
                    <span style="font-size:.7rem;color:rgb(var(--gray-400));">/proc restricted</span>
                @endif
            </x-slot>
        </x-webkernel::card compact >

        {{-- System RAM --}}
        @php
            $ramPct   = $procAvailable ? $sysRam->percentage() : 0.0;
            $ramTone  = $procAvailable ? ($ramPct > 90 ? 'red' : ($ramPct > 75 ? 'amber' : 'emerald')) : 'gray';
            $ramColor = $procAvailable ? $css($ramPct) : 'rgb(var(--gray-400))';
            $ramWidth = $procAvailable ? $logW($ramPct) : 0.0;
        @endphp
        <x-webkernel::card compact  :tone="$ramTone" :disabled="!$procAvailable">
            <x-slot name="header">
                <span style="font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgb(var(--gray-400));">SYS RAM</span>
            </x-slot>

            @if ($procAvailable)
                <div style="height:3px;background:rgb(var(--gray-200));border-radius:9999px;overflow:hidden;margin:.15rem 0;">
                    <div style="height:100%;width:{{ $ramWidth }}%;background:{{ $ramColor }};border-radius:9999px;transition:width .65s cubic-bezier(.4,0,.2,1);"></div>
                </div>
                <div style="font-size:.72rem;color:rgb(var(--gray-400));font-variant-numeric:tabular-nums;">
                    {{ $sysRam->humanUsed() }} / {{ $sysRam->humanTotal() }}
                </div>
            @else
                <x-filament::badge color="gray" style="margin-top:.25rem;">unavailable</x-filament::badge>
            @endif

            <x-slot name="meta">
                @if ($procAvailable)
                    <span style="font-weight:700;font-variant-numeric:tabular-nums;color:{{ $ramColor }};font-size:1.1rem;">{{ number_format($ramPct,1) }}%</span>
                    @unless (webkernel()->security()->isProduction())
                        @if ($sysRam->cached() > 0)
                            <span style="font-size:.68rem;color:rgb(var(--gray-400));">{{ \Webkernel\System\Support\ByteFormatter::format($sysRam->cached()) }} cached</span>
                        @endif
                    @endunless
                @else
                    <span style="font-size:.7rem;color:rgb(var(--gray-400));">/proc restricted</span>
                @endif
            </x-slot>
        </x-webkernel::card compact >

        {{-- Disk --}}
        @php
            $diskPct   = webkernel()->host()->disk()->percentage();
            $diskTone  = $diskPct > 90 ? 'red' : ($diskPct > 75 ? 'amber' : 'emerald');
            $diskColor = $css($diskPct);
            $diskWidth = $logW($diskPct);
        @endphp
        <x-webkernel::card compact  :tone="$diskTone">
            <x-slot name="header">
                <span style="font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgb(var(--gray-400));">DISK</span>
            </x-slot>

            <div style="height:3px;background:rgb(var(--gray-200));border-radius:9999px;overflow:hidden;margin:.15rem 0;">
                <div style="height:100%;width:{{ $diskWidth }}%;background:{{ $diskColor }};border-radius:9999px;transition:width .65s cubic-bezier(.4,0,.2,1);"></div>
            </div>
            <div style="font-size:.72rem;color:rgb(var(--gray-400));">
                {{ webkernel()->host()->disk()->humanUsed() }} / {{ webkernel()->host()->disk()->humanTotal() }}
                &middot; {{ webkernel()->host()->disk()->humanFree() }} free
            </div>

            <x-slot name="meta">
                <span style="font-weight:700;font-variant-numeric:tabular-nums;color:{{ $diskColor }};font-size:1.1rem;">{{ number_format($diskPct,1) }}%</span>
            </x-slot>
        </x-webkernel::card compact >

        {{-- PHP-FPM --}}
        @if ($hasFpm)
            @php
                $fpmPct   = (float) ($fpm->percentage() ?? 0.0);
                $fpmTone  = $fpmPct > 90 ? 'red' : ($fpmPct > 70 ? 'amber' : 'emerald');
                $fpmColor = $css($fpmPct);
                $fpmWidth = $logW($fpmPct);
            @endphp
            <x-webkernel::card compact  :tone="$fpmTone">
                <x-slot name="header">
                    <span style="font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgb(var(--gray-400));">PHP-FPM</span>
                </x-slot>

                <div style="height:3px;background:rgb(var(--gray-200));border-radius:9999px;overflow:hidden;margin:.15rem 0;">
                    <div style="height:100%;width:{{ $fpmWidth }}%;background:{{ $fpmColor }};border-radius:9999px;transition:width .65s cubic-bezier(.4,0,.2,1);"></div>
                </div>
                <div style="font-size:.72rem;color:rgb(var(--gray-400));font-variant-numeric:tabular-nums;">
                    {{ $fpm->active() }} / {{ $fpm->total() }} workers active
                </div>

                <x-slot name="meta">
                    <span style="font-weight:700;font-variant-numeric:tabular-nums;color:{{ $fpmColor }};font-size:1.1rem;">{{ number_format($fpmPct,1) }}%</span>
                </x-slot>
            </x-webkernel::card compact >
        @else
            <x-webkernel::card compact  disabled>
                <x-slot name="header">
                    <span style="font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgb(var(--gray-400));">PHP-FPM</span>
                </x-slot>
                <x-filament::badge color="gray" >not detected</x-filament::badge>
            </x-webkernel::card compact >
        @endif

    </div>

    {{-- SWAP --}}
    @if ($hasSwap)
        @php
            $swapTone  = $swapPct > 80 ? 'red' : ($swapPct > 40 ? 'amber' : 'emerald');
            $swapColor = $css($swapPct);
            $swapWidth = $logW($swapPct);
        @endphp
        <div style="font-weight:800;letter-spacing:.16em;text-transform:uppercase;color:rgb(var(--gray-400));padding:0 .1rem;margin-top:.25rem;font-size:.68rem;">SWAP</div>

        <x-webkernel::card compact  :tone="$swapTone">
            <x-slot name="header">
                <span style="font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgb(var(--gray-400));">Swap</span>
            </x-slot>

            <div style="height:3px;background:rgb(var(--gray-200));border-radius:9999px;overflow:hidden;margin:.15rem 0;">
                <div style="height:100%;width:{{ $swapWidth }}%;background:{{ $swapColor }};border-radius:9999px;transition:width .65s cubic-bezier(.4,0,.2,1);"></div>
            </div>
            <div style="font-size:.72rem;color:rgb(var(--gray-400));font-variant-numeric:tabular-nums;">
                {{ $sysRam->humanSwapUsed() }} / {{ $sysRam->humanSwapTotal() }}
            </div>

            <x-slot name="meta">
                <span style="font-weight:700;font-variant-numeric:tabular-nums;color:{{ $swapColor }};font-size:1.1rem;">{{ number_format($swapPct,1) }}%</span>
                @if ($swapPct > 20)
                    <x-filament::badge :color="$swapPct > 60 ? 'danger' : 'warning'" >swap pressure</x-filament::badge>
                @else
                    <x-filament::badge color="success" >healthy</x-filament::badge>
                @endif
            </x-slot>
        </x-webkernel::card compact >
    @endif

    {{-- ENVIRONMENT --}}
    <div style="font-weight:800;letter-spacing:.16em;text-transform:uppercase;color:rgb(var(--gray-400));padding:0 .1rem;margin-top:.25rem;font-size:.68rem;">ENVIRONMENT</div>

    <div class="wk-env-grid">

        {{-- PHP Runtime --}}
        <x-webkernel::card compact >
            <x-slot name="leading">
                <x-filament::icon icon="heroicon-o-code-bracket" style="width:1.1rem;height:1.1rem;color:rgb(var(--gray-400));" />
            </x-slot>
            <x-slot name="header">
                <span style="font-size:.78rem;font-weight:600;color:rgb(var(--gray-700));">PHP</span>
            </x-slot>
            <div class="wk-info-table">
                <div class="wk-info-row"><span>Version</span><x-filament::badge color="info" >{{ webkernel()->instance()->php()->version() }}</x-filament::badge></div>
                <div class="wk-info-row"><span>SAPI</span><x-filament::badge color="gray" >{{ webkernel()->runtime()->sapi()->value }}</x-filament::badge></div>
                <div class="wk-info-row"><span>Extensions</span><x-filament::badge color="gray" >{{ webkernel()->instance()->php()->extensionCount() }} loaded</x-filament::badge></div>
                <div class="wk-info-row">
                    <span>INI</span>
                    <span class="wk-info-val" title="{{ webkernel()->instance()->php()->iniFile() }}">{{ basename((string) webkernel()->instance()->php()->iniFile()) ?: '—' }}</span>
                </div>
            </div>
        </x-webkernel::card compact >

        {{-- Host --}}
        <x-webkernel::card compact >
            <x-slot name="leading">
                <x-filament::icon icon="heroicon-o-server" style="width:1.1rem;height:1.1rem;color:rgb(var(--gray-400));" />
            </x-slot>
            <x-slot name="header">
                <span style="font-size:.78rem;font-weight:600;color:rgb(var(--gray-700));">Host</span>
            </x-slot>
            <div class="wk-info-table">
                <div class="wk-info-row"><span>OS</span><x-filament::badge color="gray" >{{ webkernel()->os()->name() }}</x-filament::badge></div>
                <div class="wk-info-row"><span>Arch</span><x-filament::badge color="gray" >{{ webkernel()->os()->architecture() }}</x-filament::badge></div>
                <div class="wk-info-row"><span>Kernel</span><span class="wk-info-val">{{ webkernel()->os()->kernelRelease() }}</span></div>
                @unless (webkernel()->security()->isProduction())
                    @if (webkernel()->runtime()->serverSoftware())
                        <div class="wk-info-row"><span>Server</span><span class="wk-info-val">{{ webkernel()->runtime()->serverSoftware() }}</span></div>
                    @endif
                    @if (webkernel()->runtime()->serverAddress())
                        <div class="wk-info-row">
                            <span>Addr</span>
                            <x-filament::badge color="gray" >{{ webkernel()->security()->maskIp(webkernel()->runtime()->serverAddress()) }}{{ webkernel()->runtime()->serverPort() ? ':'.webkernel()->runtime()->serverPort() : '' }}</x-filament::badge>
                        </div>
                    @endif
                @endunless
            </div>
        </x-webkernel::card compact >

        {{-- Processes --}}
        <x-webkernel::card compact >
            <x-slot name="leading">
                <x-filament::icon icon="heroicon-o-cpu-chip" style="width:1.1rem;height:1.1rem;color:rgb(var(--gray-400));" />
            </x-slot>
            <x-slot name="header">
                <span style="font-size:.78rem;font-weight:600;color:rgb(var(--gray-700));">Processes</span>
            </x-slot>
            <div class="wk-info-table">
                @if ($procAvailable)
                    <div class="wk-info-row"><span>Total</span><x-filament::badge color="gray" >{{ webkernel()->host()->processes()->count() }}</x-filament::badge></div>
                @endif
                <div class="wk-info-row"><span>CPU cores</span><x-filament::badge color="gray" >{{ webkernel()->host()->cpu()->cores() }}</x-filament::badge></div>
                @if ($procAvailable)
                    @php $uptimeHuman = webkernel()->host()->uptime()->human(); @endphp
                    @if ($uptimeHuman !== '')
                        <div class="wk-info-row"><span>Uptime</span><x-filament::badge color="success" >{{ $uptimeHuman }}</x-filament::badge></div>
                    @endif
                @endif
                @php $entropyBits = $procAvailable ? webkernel()->host()->entropy() : 0; @endphp
                @if ($entropyBits > 0)
                    <div class="wk-info-row">
                        <span>Entropy</span>
                        <x-filament::badge :color="$entropyBits < 100 ? 'warning' : 'success'" >{{ $entropyBits }} bits</x-filament::badge>
                    </div>
                @endif
            </div>
        </x-webkernel::card compact >

        {{-- Laravel --}}
        <x-webkernel::card compact >
            <x-slot name="leading">
                <x-filament::icon icon="heroicon-o-cog-6-tooth" style="width:1.1rem;height:1.1rem;color:rgb(var(--gray-400));" />
            </x-slot>
            <x-slot name="header">
                <span style="font-size:.78rem;font-weight:600;color:rgb(var(--gray-700));">Laravel</span>
            </x-slot>
            <div class="wk-info-table">
                <div class="wk-info-row"><span>Version</span><x-filament::badge color="gray" >{{ webkernel()->app()->laravelVersion() }}</x-filament::badge></div>
                <div class="wk-info-row"><span>Env</span><x-filament::badge :color="webkernel()->app()->environment()==='production'?'success':'warning'" >{{ webkernel()->app()->environment() }}</x-filament::badge></div>
                <div class="wk-info-row"><span>Debug</span><x-filament::badge :color="webkernel()->app()->debug()?'danger':'success'" >{{ webkernel()->app()->debug()?'ON':'off' }}</x-filament::badge></div>
                <div class="wk-info-row"><span>Cache</span><x-filament::badge color="gray" >{{ webkernel()->app()->cacheDriver() }}</x-filament::badge></div>
                <div class="wk-info-row"><span>Queue</span><x-filament::badge color="gray" >{{ webkernel()->app()->queueDriver() }}</x-filament::badge></div>
            </div>
        </x-webkernel::card compact >

    </div>

    {{-- FFI DEGRADED CALLOUT --}}
    @if ($degraded)
        <x-filament::modal id="wk-ffi-modal" width="lg">
            <x-slot name="heading">Enable FFI for full metrics</x-slot>
            <x-slot name="description">
                @if ($ffiMode === 'preload')
                    <strong>FFI is active but set to <code>ffi.enable=preload</code>.</strong>
                    Preload mode only exposes definitions loaded at startup — dynamic runtime reads
                    required for system metrics need <code>ffi.enable=true</code>.
                @else
                    FFI extension not loaded or <code>ffi.enable=false</code>.
                @endif
                Active config: <code>{{ webkernel()->instance()->php()->iniFile() ?? 'run php --ini' }}</code>
            </x-slot>
            <div x-data="{ copied: false }" style="position:relative;">
                <pre id="wk-ffi-snippet" style="background:rgb(17,24,39);color:rgb(229,231,235);border-radius:.5rem;padding:1rem;overflow-x:auto;font-family:ui-monospace,monospace;margin:0;font-size:.8rem;line-height:1.6;">{{ $phpIniSnippet }}</pre>
                <div style="position:absolute;top:.4rem;right:.4rem;">
                    <x-filament::icon-button
                        x-on:click="navigator.clipboard.writeText(document.getElementById('wk-ffi-snippet').innerText).then(()=>{copied=true;setTimeout(()=>copied=false,2000);})"
                        x-bind:icon="copied?'heroicon-m-check':'heroicon-m-clipboard'"
                        x-bind:color="copied?'success':'gray'"
                        size="sm"
                    />
                </div>
            </div>
            <x-slot name="footer">
                <x-filament::button color="gray" x-on:click="$dispatch('close-modal',{id:'wk-ffi-modal'})">Close</x-filament::button>
            </x-slot>
        </x-filament::modal>

        <x-filament::callout
            icon="heroicon-o-exclamation-triangle"
            color="warning"
            icon-size="sm"
        >
            <x-slot name="heading">
                FFI Status
            </x-slot>

            <x-slot name="description">
                @if ($ffiMode === 'preload')
                    FFI set to <strong>preload</strong> — dynamic reads unavailable, /proc fallback active
                @else
                    FFI not active — /proc fallback active (less precise)
                @endif
            </x-slot>

            <x-slot name="footer">
                <x-filament::button
                    color="warning"
                    icon="heroicon-m-document-text"
                    size="sm"
                    x-on:click="$dispatch('open-modal',{id:'wk-ffi-modal'})"
                >
                    Fix php.ini
                </x-filament::button>
            </x-slot>
        </x-filament::callout>

    @endif

    {{-- POLL COUNTDOWN BAR --}}
    <div style="height:2px;background:rgb(var(--gray-100));border-radius:9999px;overflow:hidden;">
        <div x-bind:style="`width:${(elapsed/5)*100}%;transition:width 1s linear;`"
             style="height:100%;background:rgb(var(--gray-300));border-radius:9999px;"></div>
    </div>

</div>

@once
<style id="wk-pulse-styles">
    @keyframes wk-ping { 75%,100%{transform:scale(2.2);opacity:0;} }
    .wk-primary-grid  { display:grid;gap:.5rem;grid-template-columns:1fr; }
    .wk-context-grid  { display:grid;gap:.5rem;grid-template-columns:1fr; }
    .wk-env-grid      { display:grid;gap:.5rem;grid-template-columns:1fr; }
    @media(min-width:480px)  { .wk-primary-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .wk-context-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .wk-env-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media(min-width:900px)  { .wk-primary-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } .wk-context-grid { grid-template-columns:repeat(4,minmax(0,1fr)); } .wk-env-grid { grid-template-columns:repeat(4,minmax(0,1fr)); } }
    .wk-info-table { display:flex;flex-direction:column;gap:.2rem;margin-top:.2rem; }
    .wk-info-row   { display:flex;align-items:center;justify-content:space-between;gap:.4rem;font-size:.72rem; }
    .wk-info-row > span:first-child { color:rgb(var(--gray-400));white-space:nowrap;flex-shrink:0; }
    .wk-info-val { color:rgb(var(--gray-500));overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:120px;font-size:.72rem; }
</style>
@endonce
