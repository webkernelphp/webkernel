<x-filament-panels::page>
@php
    $semver   = $this->release['semver'];
    $codename = $this->release['codename'];
@endphp

{{-- ══════════════════════════════════════════════════════
     HERO
══════════════════════════════════════════════════════ --}}
<x-filament::section>
    <div style="display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:1rem;">

        <div style="display:flex; flex-direction:column; gap:0.4rem;">
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:0.5rem;">
                <span style="font-size:1.5rem; letter-spacing:-0.03em;">
                  Webkernel<sup>™</sup>
                  <span style="color:var(--primary-600);">{{ $codename }}</span>
                </span>
                <x-filament::badge color="primary" size="lg">{{ $semver }}</x-filament::badge>
            </div>
            <p style="font-size:0.8rem; color:var(--gray-400,#94a3b8); margin:0;">
                Dependency &amp; Module Manager · Vendor snapshots · CVE auditing · Updates · Store
            </p>
        </div>

        <div style="display:flex; flex-wrap:wrap; align-items:center; gap:1.5rem;">
            @foreach([
                ['label'=>'packages',  'value'=> $this->total,           'color'=> 'primary'],
                ['label'=>'CVEs',      'value'=> $this->vulnerableCount,  'color'=> $this->vulnerableCount > 0 ? 'danger'  : 'success'],
                ['label'=>'updates',   'value'=> $this->outdatedCount,    'color'=> $this->outdatedCount   > 0 ? 'warning' : 'success'],
                ['label'=>'modules',   'value'=> $this->activeModules,    'color'=> 'info'],
            ] as $q)
            <div style="display:flex; align-items:center; gap:0.4rem;">
                <x-filament::badge :color="$q['color']">{{ $q['value'] }}</x-filament::badge>
                <span style="font-size:0.78rem; color:var(--gray-400,#94a3b8);">{{ $q['label'] }}</span>
            </div>
            @endforeach
        </div>
    </div>
</x-filament::section>

{{-- ══════════════════════════════════════════════════════
     STAT WIDGETS — CSS grid fixe 7 colonnes
══════════════════════════════════════════════════════ --}}
<div style="display:grid; grid-template-columns:repeat(7,1fr); gap:0.75rem;">
    @foreach([
        ['label'=>'Total',       'value'=>$this->total,          'color'=>'primary', 'icon'=>'heroicon-o-cube'],
        ['label'=>'Core',        'value'=>$this->coreCount,       'color'=>'info',    'icon'=>'heroicon-o-cog-6-tooth'],
        ['label'=>'Webkernel',   'value'=>$this->webkernelCount,  'color'=>'primary', 'icon'=>'heroicon-o-star'],
        ['label'=>'Third-party', 'value'=>$this->thirdPartyCount, 'color'=>'gray',    'icon'=>'heroicon-o-globe-alt'],
        ['label'=>'CVEs',        'value'=>$this->vulnerableCount, 'color'=>$this->vulnerableCount>0?'danger':'success', 'icon'=>'heroicon-o-shield-exclamation'],
        ['label'=>'Updates',     'value'=>$this->outdatedCount,   'color'=>$this->outdatedCount>0?'warning':'success',  'icon'=>'heroicon-o-arrow-path'],
        ['label'=>'Modules',     'value'=>$this->activeModules,   'color'=>'success', 'icon'=>'heroicon-o-puzzle-piece'],
    ] as $s)
    <x-filament::section :compact="true">
        <div style="display:flex; flex-direction:column; gap:0.4rem; align-items:flex-start;">
            <x-filament::icon-button :icon="$s['icon']" :color="$s['color']" size="sm" disabled />
            <span style="font-size:1.6rem;  line-height:1;">{{ $s['value'] }}</span>
            <span style="font-size:0.65rem; text-transform:uppercase; letter-spacing:.07em; color:var(--gray-400,#94a3b8);">{{ $s['label'] }}</span>
        </div>
    </x-filament::section>
    @endforeach
</div>

{{-- ══════════════════════════════════════════════════════
     TABS — Filament natif
══════════════════════════════════════════════════════ --}}
<x-filament::tabs>
    <x-filament::tabs.item
        :active="$this->activeTab === 'dependencies'"
        wire:click="$set('activeTab','dependencies')"
        icon="heroicon-o-cube"
        :badge="$this->total"
    >Dependencies</x-filament::tabs.item>

    <x-filament::tabs.item
        :active="$this->activeTab === 'modules'"
        wire:click="$set('activeTab','modules')"
        icon="heroicon-o-puzzle-piece"
        :badge="$this->activeModules"
        badge-color="success"
    >Installed Modules</x-filament::tabs.item>

    <x-filament::tabs.item
        :active="$this->activeTab === 'store'"
        wire:click="$set('activeTab','store')"
        icon="heroicon-o-shopping-bag"
        badge="NEW"
        badge-color="warning"
    >Module Store</x-filament::tabs.item>
</x-filament::tabs>


{{-- ══════════════════════════════════════════════════════
     TAB: DEPENDENCIES
══════════════════════════════════════════════════════ --}}
@if($this->activeTab === 'dependencies')

{{-- Filter slide-over --}}
<x-filament::modal
    id="dep-filters"
    :visible="$this->filtersOpen"
    wire:model="filtersOpen"
    slide-over
    width="sm"
    heading="Filter Packages"
>
    <div style="display:flex; flex-direction:column; gap:1.25rem;">

        <x-filament::fieldset legend="Search">
            <x-filament::input.wrapper leading-icon="heroicon-o-magnifying-glass">
                <x-filament::input type="text" wire:model.live.debounce.250ms="search" placeholder="Name or description…" />
            </x-filament::input.wrapper>
        </x-filament::fieldset>

        <x-filament::fieldset legend="Package type">
            <div style="display:flex; flex-wrap:wrap; gap:0.4rem;">
                @foreach(['all'=>'All','core'=>'Core','webkernel'=>'Webkernel','third-party'=>'Third-party'] as $k=>$l)
                <x-filament::button wire:click="$set('typeFilter','{{$k}}')" size="xs" :color="$this->typeFilter===$k?'primary':'gray'">{{$l}}</x-filament::button>
                @endforeach
            </div>
        </x-filament::fieldset>

        <x-filament::fieldset legend="Security">
            <div style="display:flex; flex-wrap:wrap; gap:0.4rem;">
                @foreach(['all'=>'All','secure'=>'Secure','vulnerable'=>'CVE found'] as $k=>$l)
                <x-filament::button wire:click="$set('securityFilter','{{$k}}')" size="xs"
                    :color="$this->securityFilter===$k ? ($k==='vulnerable'?'danger':($k==='secure'?'success':'primary')) : 'gray'"
                >{{$l}}</x-filament::button>
                @endforeach
            </div>
        </x-filament::fieldset>

        <x-filament::fieldset legend="Updates">
            <div style="display:flex; flex-wrap:wrap; gap:0.4rem;">
                @foreach(['all'=>'All','outdated'=>'Outdated only'] as $k=>$l)
                <x-filament::button wire:click="$set('updateFilter','{{$k}}')" size="xs" :color="$this->updateFilter===$k?'warning':'gray'">{{$l}}</x-filament::button>
                @endforeach
            </div>
        </x-filament::fieldset>

        <x-filament::fieldset legend="License">
            <div style="display:flex; flex-wrap:wrap; gap:0.4rem;">
                <x-filament::button wire:click="$set('licenseFilter','all')" size="xs" :color="$this->licenseFilter==='all'?'primary':'gray'">All</x-filament::button>
                @foreach($this->uniqueLicenses as $lic)
                <x-filament::button wire:click="$set('licenseFilter','{{$lic}}')" size="xs" :color="$this->licenseFilter===$lic?'primary':'gray'">{{$lic}}</x-filament::button>
                @endforeach
            </div>
        </x-filament::fieldset>
    </div>

    <x-slot name="footerActions">
        @if($this->hasActiveFilters())
        <x-filament::button color="danger" size="sm" wire:click="resetFilters">Clear all</x-filament::button>
        @endif
        <x-filament::button color="gray" size="sm" wire:click="$set('filtersOpen',false)">Close</x-filament::button>
    </x-slot>
</x-filament::modal>

{{-- Toolbar --}}
<div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:0.75rem;">
    <x-filament::input.wrapper leading-icon="heroicon-o-magnifying-glass" style="width:16rem;">
        <x-filament::input type="text" wire:model.live.debounce.250ms="search" placeholder="Quick search…" />
    </x-filament::input.wrapper>

    <div style="display:flex; flex-wrap:wrap; gap:0.5rem; align-items:center;">
        <x-filament::button wire:click="$set('filtersOpen',true)" size="sm" :color="$this->hasActiveFilters()?'primary':'gray'" icon="heroicon-o-adjustments-horizontal">
            Filters{{ $this->hasActiveFilters() ? ' ●' : '' }}
        </x-filament::button>
        @if($this->hasActiveFilters())
        <x-filament::button wire:click="resetFilters" size="sm" color="danger" icon="heroicon-o-x-mark">Reset</x-filament::button>
        @endif
        <x-filament::button wire:click="refreshCves"     size="sm" color="danger"  icon="heroicon-o-shield-exclamation" wire:loading.attr="disabled">Audit CVE</x-filament::button>
        <x-filament::button wire:click="refreshOutdated" size="sm" color="warning" icon="heroicon-o-arrow-path"         wire:loading.attr="disabled">Updates</x-filament::button>
    </div>
</div>

{{-- Active filter badges --}}
@if($this->hasActiveFilters())
<div style="display:flex; flex-wrap:wrap; gap:0.4rem; align-items:center;">
    @if($this->typeFilter!=='all')
    <x-filament::badge color="primary" wire:click="$set('typeFilter','all')" style="cursor:pointer;">type: {{$this->typeFilter}} ×</x-filament::badge>
    @endif
    @if($this->securityFilter!=='all')
    <x-filament::badge :color="$this->securityFilter==='vulnerable'?'danger':'success'" wire:click="$set('securityFilter','all')" style="cursor:pointer;">security: {{$this->securityFilter}} ×</x-filament::badge>
    @endif
    @if($this->updateFilter!=='all')
    <x-filament::badge color="warning" wire:click="$set('updateFilter','all')" style="cursor:pointer;">outdated only ×</x-filament::badge>
    @endif
    @if($this->licenseFilter!=='all')
    <x-filament::badge color="gray" wire:click="$set('licenseFilter','all')" style="cursor:pointer;">license: {{$this->licenseFilter}} ×</x-filament::badge>
    @endif
    @if($this->search!=='')
    <x-filament::badge color="gray" wire:click="$set('search','')" style="cursor:pointer;">"{{ Str::limit($this->search,20) }}" ×</x-filament::badge>
    @endif
    <span style="font-size:0.72rem; color:var(--gray-400,#94a3b8);">{{ $this->filteredCount }} / {{ $this->total }} shown</span>
</div>
@endif

{{-- Empty state --}}
@if(empty($this->filteredGrouped))
<x-filament::section>
    <x-filament::empty-state
        icon="heroicon-o-magnifying-glass"
        heading="No packages match"
        description="Try adjusting your filters or search query."
    >
        <x-slot name="actions">
            <x-filament::button color="primary" wire:click="resetFilters">Clear filters</x-filament::button>
        </x-slot>
    </x-filament::empty-state>
</x-filament::section>
@else

{{-- Vendor groups --}}
@foreach($this->filteredGrouped as $vendor => $vendorPackages)
<x-filament::section :collapsible="true"  :contained="false">
    <x-slot name="heading">
        @php
            $vendorFirstPackage = reset($vendorPackages);
            $authorEmail = (is_array($vendorFirstPackage) ? ($vendorFirstPackage['author_email'] ?? '') : '') ?: null;

            // Consistent color based on vendor name
            $hue = crc32($vendor) % 360;
            $avatarUrl = $authorEmail ? 'https://www.gravatar.com/avatar/'.md5(strtolower(trim($authorEmail))).'?d=404&s=64' : null;

            // Shared dimensions/style to ensure "same look"
            $avatarStyle = "width:2rem; height:2rem; border-radius:9999px; flex-shrink:0; background:#fff; border:1px solid rgba(0,0,0,0.1); display:flex; align-items:center; justify-content:center; overflow:hidden;";
        @endphp

        <div style="display:flex; align-items:center; gap:0.5rem;">
            <div style="{{ $avatarStyle }}">
                @if($avatarUrl)
                    <img src="{{ $avatarUrl }}" alt="{{ $vendor }}" style="width:100%; height:100%; object-fit:cover;">
                @else
                    <div style="width:100%; height:100%; background:hsl({{ $hue }}, 70%, 80%); display:flex; align-items:center; justify-content:center; font-size:0.75rem; color:hsl({{ $hue }}, 70%, 30%);">
                        {{ substr($vendor, 0, 1) }}
                    </div>
                @endif
            </div>

            <div style="display:flex; flex-direction:column; line-height:1;">
                <span style="">{{ $vendor }}</span>
                @if($authorEmail)
                    <span style="font-size:0.75rem; color:#64748b;">{{ $authorEmail }}</span>
                @endif
            </div>

            <x-filament::badge color="gray" size="sm">{{ count($vendorPackages) }}</x-filament::badge>
        </div>
    </x-slot>

    {{-- GRID: 4 colonnes CSS pur, garanti --}}
    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:0.6rem;">

        @foreach($vendorPackages as $fullName => $pkg)
        @php
            $hasCve    = isset($this->cves[$fullName]);
            $hasUpdate = isset($this->outdated[$fullName]);
            $cveList   = $this->cves[$fullName]    ?? [];
            $upInfo    = $this->outdated[$fullName] ?? null;
            $typeColor = match($pkg['type']) { 'core'=>'info', 'webkernel'=>'primary', default=>'gray' };
            $secColor  = $hasCve ? 'danger' : 'success';
            $secLabel  = $hasCve ? '⚠ CVE'  : '✓ Secure';
        @endphp

        <x-filament::section :compact="true" x-data="{cveOpen:false}">

            {{-- Package name + type --}}
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:0.25rem; flex-wrap:wrap;">
                <span style=" font-size:0.82rem; word-break:break-all; line-height:1.3;">{{ $pkg['package'] }}</span>
                <x-filament::badge :color="$typeColor" size="sm">{{ $pkg['type'] }}</x-filament::badge>
            </div>

            {{-- Description --}}
            @if($pkg['description'])
            <p style="font-size:0.7rem; color:var(--gray-400,#94a3b8); margin:.25rem 0 0; line-height:1.4; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;">{{ $pkg['description'] }}</p>
            @endif

            {{-- Badges row --}}
            <div style="display:flex; flex-wrap:wrap; gap:0.25rem; margin-top:0.5rem;">
                <x-filament::badge color="gray"    size="sm">{{ $pkg['version'] }}</x-filament::badge>
                <x-filament::badge color="gray"    size="sm">{{ $pkg['license'] }}</x-filament::badge>
                <x-filament::badge :color="$secColor" size="sm">{{ $secLabel }}</x-filament::badge>
                @if($hasUpdate)
                <x-filament::badge color="warning" size="sm">↑ {{ $upInfo['latest'] }}</x-filament::badge>
                @endif
            </div>

            {{-- CVE details --}}
            @if($hasCve)
            <div style="margin-top:0.5rem;">
                <button @click="cveOpen=!cveOpen" style="font-size:0.7rem;  background:none; border:none; cursor:pointer; padding:0; color:var(--danger-500,#ef4444);">
                    <span x-text="cveOpen ? '▲ hide' : '▼ ' + {{ count($cveList) }} + ' CVE(s)'"></span>
                </button>
                <div x-show="cveOpen" x-collapse style="margin-top:0.4rem; display:flex; flex-direction:column; gap:0.25rem;">
                    @foreach($cveList as $c)
                    <x-filament::callout icon="heroicon-o-exclamation-triangle" color="danger">
                        <span style="">{{ $c['cve'] ?? 'Advisory' }}</span> — {{ $c['title'] }}
                    </x-filament::callout>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Actions --}}
            <div style="display:flex; justify-content:flex-end; gap:0.25rem; margin-top:0.5rem;">
                @if($pkg['homepage'])
                <x-filament::icon-button icon="heroicon-o-arrow-top-right-on-square" size="sm" color="gray" tooltip="Homepage" tag="a" :href="$pkg['homepage']" target="_blank" />
                @endif
                <x-filament::dropdown>
                    <x-slot name="trigger">
                        <x-filament::icon-button icon="heroicon-o-archive-box-arrow-down" size="sm" color="gray" tooltip="Vendorize" />
                    </x-slot>
                    <x-filament::dropdown.list>
                        <x-filament::dropdown.list.item icon="heroicon-o-server"        wire:click="openVendorModal('{{ $fullName }}','local')">Save locally</x-filament::dropdown.list.item>
                        <x-filament::dropdown.list.item icon="heroicon-o-cloud-arrow-up" wire:click="openVendorModal('{{ $fullName }}','github')">Push to GitHub</x-filament::dropdown.list.item>
                    </x-filament::dropdown.list>
                </x-filament::dropdown>
            </div>

        </x-filament::section>
        @endforeach

    </div>{{-- /grid --}}
</x-filament::section>
@endforeach

@endif {{-- /filtered --}}
@endif {{-- /tab dependencies --}}


{{-- ══════════════════════════════════════════════════════
     TAB: INSTALLED MODULES
══════════════════════════════════════════════════════ --}}
@if($this->activeTab === 'modules')

@if(empty($this->modules))
<x-filament::section>
    <x-filament::empty-state
        icon="heroicon-o-puzzle-piece"
        heading="No modules installed"
        description="Visit the Module Store to discover and install extensions."
    >
        <x-slot name="actions">
            <x-filament::button color="primary" icon="heroicon-o-shopping-bag" wire:click="$set('activeTab','store')">Open Store</x-filament::button>
        </x-slot>
    </x-filament::empty-state>
</x-filament::section>
@else

{{-- Module list — layout en flex row, compact --}}
<div style="display:flex; flex-direction:column; gap:0.6rem;">
@foreach($this->modules as $mod)
@php
    $partyColor = match($mod['party'] ?? 'second') { 'first'=>'primary', default=>'gray' };
    $partyLabel = match($mod['party'] ?? 'second') { 'first'=>'Official', default=>'Community' };
@endphp
<x-filament::section :compact="true">
    <div style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">

        <x-filament::avatar :src="$mod['image'] ?? null" :alt="$mod['label']" size="md" />

        <div style="flex:1; min-width:0;">
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:0.4rem;">
                <span style=" font-size:0.92rem;">{{ $mod['label'] }}</span>
                <x-filament::badge :color="$partyColor"                    size="sm">{{ $partyLabel }}</x-filament::badge>
                <x-filament::badge :color="($mod['active']??false)?'success':'gray'" size="sm">{{ ($mod['active']??false)?'Active':'Inactive' }}</x-filament::badge>
                <x-filament::badge color="gray" size="sm">v{{ $mod['version'] }}</x-filament::badge>
                <x-filament::badge color="gray" size="sm">{{ $mod['license'] }}</x-filament::badge>
            </div>
            <p style="font-size:0.76rem; color:var(--gray-400,#94a3b8); margin:.15rem 0 0;">{{ $mod['description'] ?? '' }}</p>
            <div style="display:flex; flex-wrap:wrap; gap:1.25rem; margin-top:0.3rem; font-size:0.7rem; color:var(--gray-400,#94a3b8);">
                <span><strong>Registry:</strong> {{ $mod['registry'] ?? '—' }}</span>
                <span><strong>PHP:</strong> {{ $mod['compatibility']['php'] ?? '—' }}</span>
                <span><strong>Laravel:</strong> {{ $mod['compatibility']['laravel'] ?? '—' }}</span>
                <span><strong>Created:</strong> {{ \Carbon\Carbon::parse($mod['created_at'])->format('d M Y') }}</span>
            </div>
        </div>

        <div style="display:flex; gap:0.4rem; flex-shrink:0;">
            @if($mod['docs_link'] ?? null)
            <x-filament::button size="sm" color="gray" icon="heroicon-o-document-text" tag="a" :href="$mod['docs_link']" target="_blank">Docs</x-filament::button>
            @endif
            <x-filament::button size="sm" :color="($mod['active']??false)?'danger':'success'" :icon="($mod['active']??false)?'heroicon-o-pause':'heroicon-o-play'">
                {{ ($mod['active']??false) ? 'Disable' : 'Enable' }}
            </x-filament::button>
        </div>

    </div>
</x-filament::section>
@endforeach
</div>

@endif
@endif {{-- /tab modules --}}


{{-- ══════════════════════════════════════════════════════
     TAB: MODULE STORE
══════════════════════════════════════════════════════ --}}
@if($this->activeTab === 'store')

{{-- Store toolbar --}}
<x-filament::section :compact="true">
    <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:0.75rem;">
        <div>
            <p style=" font-size:1rem; margin:0;">Webkernel™ Module Store</p>
            <p style="font-size:0.76rem; color:var(--gray-400,#94a3b8); margin:.15rem 0 0;">Discover, install and manage extensions.</p>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:0.5rem; align-items:center;">
            <x-filament::input.wrapper leading-icon="heroicon-o-magnifying-glass">
                <x-filament::input type="text" wire:model.live.debounce.250ms="storeSearch" placeholder="Search modules…" />
            </x-filament::input.wrapper>
            @foreach(['all'=>'All','first'=>'Official','second'=>'Community'] as $k=>$l)
            <x-filament::button wire:click="$set('storeParty','{{$k}}')" size="sm" :color="$this->storeParty===$k?'primary':'gray'">{{$l}}</x-filament::button>
            @endforeach
            @foreach(['all'=>'Any price','free'=>'Free','premium'=>'Premium'] as $k=>$l)
            <x-filament::button wire:click="$set('storePrice','{{$k}}')" size="sm" :color="$this->storePrice===$k?'warning':'gray'">{{$l}}</x-filament::button>
            @endforeach
        </div>
    </div>
</x-filament::section>

<p style="font-size:0.72rem; color:var(--gray-400,#94a3b8);">
    {{ count($this->filteredStoreModules) }} / {{ count($this->storeModules) }} modules ·
    {{ count(array_filter($this->storeModules, fn($m) => $m['installed'])) }} installed
</p>

@if(empty($this->filteredStoreModules))
<x-filament::section>
    <x-filament::empty-state icon="heroicon-o-shopping-bag" heading="No modules found" description="Adjust your search or filters." />
</x-filament::section>
@else

{{-- Store grid: 3 colonnes CSS --}}
<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:1rem;">
@foreach($this->filteredStoreModules as $mod)
@php
    $installed  = $mod['installed'] ?? false;
    $partyColor = ($mod['party']??'second')==='first' ? 'primary' : 'gray';
    $partyLabel = ($mod['party']??'second')==='first' ? 'Official' : 'Community';
    $priceColor = ($mod['price']??'free')==='free'    ? 'success'  : 'warning';
    $priceLabel = ($mod['price']??'free')==='free'    ? 'Free'     : 'Premium';
@endphp

<x-filament::section>

    {{-- Banner --}}
    <div style="margin:-1rem -1rem .75rem; height:120px; background:var(--gray-100,#f1f5f9); border-radius:.75rem .75rem 0 0; overflow:hidden; position:relative; display:flex; align-items:center; justify-content:center;">
        @if($mod['image'] ?? null)
            <img src="{{ $mod['image'] }}" alt="{{ $mod['label'] }}" style="width:100%; height:100%; object-fit:cover;" />
        @else
            <x-filament::icon icon="heroicon-o-puzzle-piece" style="width:3rem; height:3rem; opacity:0.3;" />
        @endif

        <div style="position:absolute; top:.5rem; left:.5rem; display:flex; gap:.25rem;">
            @if($installed)
            <x-filament::badge color="success" size="sm">✓ Installed</x-filament::badge>
            @endif
        </div>
        <div style="position:absolute; top:.5rem; right:.5rem; display:flex; gap:.25rem;">
            <x-filament::badge :color="$partyColor" size="sm">{{ $partyLabel }}</x-filament::badge>
            <x-filament::badge :color="$priceColor" size="sm">{{ $priceLabel }}</x-filament::badge>
        </div>
        @if($mod['video_link'] ?? null)
        <a href="{{ $mod['video_link'] }}" target="_blank" style="position:absolute; bottom:.4rem; right:.4rem;">
            <x-filament::badge color="gray" icon="heroicon-o-play-circle" size="sm">Demo</x-filament::badge>
        </a>
        @endif
    </div>

    {{-- Title --}}
    <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:.5rem;">
        <div>
            <p style="font-weight:800; font-size:.95rem; margin:0;">{{ $mod['label'] }}</p>
            <p style="font-size:.7rem; color:var(--gray-400,#94a3b8); margin:.1rem 0 0;">by {{ $mod['author'] }}</p>
        </div>
        <x-filament::badge color="gray" size="sm">v{{ $mod['version'] }}</x-filament::badge>
    </div>

    {{-- Description --}}
    <p style="font-size:.78rem; color:var(--gray-500,#64748b); margin:.5rem 0 0; line-height:1.5; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;">{{ $mod['description'] }}</p>

    {{-- Tags --}}
    <div style="display:flex; flex-wrap:wrap; gap:.25rem; margin-top:.5rem;">
        @foreach($mod['tags'] ?? [] as $tag)
        <x-filament::badge color="primary" size="sm">#{{ $tag }}</x-filament::badge>
        @endforeach
    </div>

    {{-- Meta --}}
    <div style="display:flex; flex-wrap:wrap; gap:.75rem; margin-top:.5rem; font-size:.72rem; color:var(--gray-400,#94a3b8);">
        @if($mod['rating'] ?? null)<span>⭐ {{ number_format($mod['rating'],1) }}</span>@endif
        <span>↓ {{ number_format($mod['downloads']) }}</span>
        <span>{{ $mod['license'] }}</span>
    </div>

    {{-- Compat --}}
    <div style="display:flex; flex-wrap:wrap; gap:.25rem; margin-top:.4rem;">
        @foreach($mod['compatibility'] ?? [] as $k=>$v)
        <x-filament::badge color="gray" size="sm">{{ $k }} {{ $v }}</x-filament::badge>
        @endforeach
    </div>

    {{-- Footer --}}
    <div style="display:flex; align-items:center; gap:.5rem; margin-top:.75rem; padding-top:.75rem; border-top:1px solid var(--gray-200,#e2e8f0);">
        @if($mod['docs_link'] ?? null)
        <x-filament::link :href="$mod['docs_link']" target="_blank" icon="heroicon-o-document-text" size="sm">Docs</x-filament::link>
        @endif
        <div style="flex:1;"></div>
        @if($installed)
        <x-filament::button size="sm" color="gray"    icon="heroicon-o-cog-6-tooth">Configure</x-filament::button>
        @else
        <x-filament::button size="sm" color="primary" icon="heroicon-o-arrow-down-tray" wire:click="installModule('{{ $mod['id'] }}')">Install</x-filament::button>
        @endif
    </div>

</x-filament::section>
@endforeach
</div>

@endif
@endif {{-- /tab store --}}


{{-- ══════════════════════════════════════════════════════
     VENDORIZE MODAL
══════════════════════════════════════════════════════ --}}
<x-filament::modal
    id="vendorize-confirm"
    :visible="$this->vendorModalOpen"
    wire:model="vendorModalOpen"
    width="md"
    :heading="'Vendorize: ' . $this->vendorTarget"
>
    @if($this->vendorMode === 'local')
    <x-filament::callout icon="heroicon-o-server" color="info">
        Snapshot destination:<br>
        <code style="font-size:.72rem; display:block; margin-top:.25rem; word-break:break-all;">
            ~/webkernel/backup-vendor/{instanceID}/{datetime}/vendor/{{ $this->vendorTarget }}
        </code>
    </x-filament::callout>
    @else
    <x-filament::callout icon="heroicon-o-cloud-arrow-up" color="primary">
        A new GitHub repository will be created and the vendor source pushed into it.
    </x-filament::callout>
    @endif

    <x-slot name="footerActions">
        <x-filament::button color="gray"    wire:click="$set('vendorModalOpen',false)">Cancel</x-filament::button>
        <x-filament::button color="primary" wire:click="confirmVendorize" icon="heroicon-o-archive-box-arrow-down">Confirm</x-filament::button>
    </x-slot>
</x-filament::modal>


{{-- ══════════════════════════════════════════════════════
     FOOTER
══════════════════════════════════════════════════════ --}}
<div style="text-align:center; font-size:.7rem; color:var(--gray-400,#94a3b8); padding-top:1.5rem; border-top:1px solid var(--gray-200,#e2e8f0); margin-top:1rem;">
    © {{ date('Y') }} Numerimondes · Webkernel™ {{ $semver }} "{{ $codename }}"
</div>

</x-filament-panels::page>
