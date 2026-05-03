<x-filament-panels::page>
    <div style="space-y: 6;">
        <!-- Hero Section with Search -->
        <div style="background: linear-gradient(135deg, var(--primary-600), var(--primary-700)); border-radius: 12px; padding: 3rem; margin-bottom: 3rem; color: white;">
            <h1 style="font-size: 2rem; font-weight: 700; margin: 0 0 0.5rem 0;">Webkernel Marketplace</h1>
            <p style="font-size: 1rem; margin: 0 0 2rem 0; opacity: 0.9;">Discover and install powerful modules to extend your application</p>

            <div style="display: flex; gap: 1rem; max-width: 600px;">
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model.live="searchQuery"
                        placeholder="Search modules..."
                        style="background: white; color: #000;"
                    />
                </x-filament::input.wrapper>
            </div>
        </div>

        <!-- Category Tabs (only show if modules exist and multiple categories) -->
        @if(!empty($this->modules) && count($this->availableCategories()) > 1)
            <div style="margin-bottom: 2rem;">
                <x-filament::tabs>
                    @foreach($this->availableCategories() as $categoryKey => $category)
                        <x-filament::tabs.item
                            :active="$selectedCategory === $categoryKey"
                            :icon="$category['icon']"
                            wire:click="selectCategory('{{ $categoryKey }}')"
                        >
                            {{ $category['label'] }}
                        </x-filament::tabs.item>
                    @endforeach
                </x-filament::tabs>
            </div>
        @endif

        <!-- Loading State -->
        @if($isLoading)
            <div style="display: flex; justify-content: center; align-items: center; padding: 4rem 0;">
                <x-filament::loading-indicator />
            </div>
        @elseif(empty($this->filteredModules()))
            <x-filament::empty-state
                icon="heroicon-o-cube-transparent"
                heading="No modules found"
                description="Try adjusting your search or category filters"
            />
        @else
            <!-- Modules Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem;">
                @forelse($this->filteredModules() as $module)
                    <!-- Module Card -->
                    <div style="background: white; border: 1px solid var(--gray-200); border-radius: 12px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: all 0.2s;">
                        <!-- Header with Icon -->
                        <div style="background: linear-gradient(135deg, var(--primary-600), var(--info-600)); padding: 2rem; text-align: center; color: white;">
                            <div style="font-size: 3rem; line-height: 1; margin-bottom: 1rem;">📦</div>
                            <h3 style="margin: 0; font-size: 1.25rem; font-weight: 600;">{{ $module['name'] }}</h3>
                        </div>

                        <!-- Content -->
                        <div style="padding: 1.5rem; flex: 1; display: flex; flex-direction: column;">
                            <!-- Author -->
                            <p style="margin: 0 0 0.75rem 0; font-size: 0.875rem; color: var(--gray-600);">
                                <span style="font-weight: 500;">{{ $module['author'] ?? 'Unknown' }}</span>
                            </p>

                            <!-- Description -->
                            <p style="margin: 0 0 1rem 0; color: var(--gray-700); font-size: 0.875rem; line-height: 1.6; flex: 1;">
                                {{ $module['description'] }}
                            </p>

                            <!-- Stats -->
                            <div style="display: flex; gap: 1.5rem; margin: 1rem 0; font-size: 0.875rem; color: var(--gray-600); padding-top: 1rem; border-top: 1px solid var(--gray-200);">
                                @if(isset($module['rating']))
                                    <div style="display: flex; align-items: center; gap: 0.25rem;">
                                        <span>⭐</span>
                                        <span style="font-weight: 600; color: var(--gray-900);">{{ number_format($module['rating'], 1) }}</span>
                                    </div>
                                @endif
                                @if(isset($module['downloads']))
                                    <div>{{ number_format($module['downloads']) }} installs</div>
                                @endif
                                @if(isset($module['version']))
                                    <div style="color: var(--gray-500);">v{{ $module['version'] }}</div>
                                @endif
                            </div>

                            <!-- Tags -->
                            @if(isset($module['tags']) && !empty($module['tags']))
                                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin: 1rem 0;">
                                    @foreach(array_slice($module['tags'], 0, 3) as $tag)
                                        <x-filament::badge size="sm" color="primary">
                                            {{ $tag }}
                                        </x-filament::badge>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <!-- Actions Footer -->
                        <div style="padding: 1rem 1.5rem; border-top: 1px solid var(--gray-200); display: flex; gap: 0.75rem;">
                            <x-filament::button
                                wire:click="installModule('{{ $module['id'] }}')"
                                style="flex: 1;"
                                size="sm"
                            >
                                Install
                            </x-filament::button>
                            <x-filament::icon-button
                                icon="heroicon-m-information-circle"
                                tooltip="Learn more"
                                size="sm"
                            />
                        </div>
                    </div>
                @empty
                    <div style="grid-column: 1 / -1; text-align: center; padding: 2rem;">
                        <p style="color: var(--gray-600);">No modules match your search</p>
                    </div>
                @endforelse
            </div>
        @endif
    </div>
</x-filament-panels::page>
