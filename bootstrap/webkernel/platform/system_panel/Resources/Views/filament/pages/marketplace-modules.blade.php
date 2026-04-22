<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Hero Section -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg p-8 text-white">
            <h1 class="text-3xl font-bold mb-2">Webkernel Marketplace</h1>
            <p class="text-lg opacity-90 mb-6">Discover and install powerful modules to extend your application</p>

            <!-- Search Input -->
            <div class="flex gap-2 max-w-md">
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model.live="searchQuery"
                        placeholder="Search modules, features, authors..."
                        class="bg-white text-gray-900"
                    />
                </x-filament::input.wrapper>
                <x-filament::button color="white">
                    Search
                </x-filament::button>
            </div>
        </div>

        <!-- Category Tabs -->
        <x-filament::tabs class="space-y-6">
            <x-filament::tabs.tab label="All Modules" active>
                <!-- This is the active tab content -->
            </x-filament::tabs.tab>
            <x-filament::tabs.tab label="Authentication"></x-filament::tabs.tab>
            <x-filament::tabs.tab label="Payments"></x-filament::tabs.tab>
            <x-filament::tabs.tab label="Communication"></x-filament::tabs.tab>
            <x-filament::tabs.tab label="Analytics"></x-filament::tabs.tab>
        </x-filament::tabs>

        <!-- Loading State -->
        @if($isLoading)
            <div class="flex justify-center items-center py-12">
                <x-filament::loading-indicator class="text-blue-600" />
            </div>
        @endif

        <!-- Empty State -->
        @if(!$isLoading && empty($this->filteredModules()))
            <x-filament::empty-state
                icon="heroicon-o-cube-transparent"
                heading="No modules found"
                description="Try adjusting your search or filters"
            />
        @endif

        <!-- Modules Grid -->
        @if(!$isLoading && !empty($this->filteredModules()))
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($this->filteredModules() as $module)
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                        <!-- Module Header -->
                        <div class="bg-gradient-to-r from-blue-600 to-purple-600 p-6 text-white text-center">
                            <div class="text-4xl mb-3">📦</div>
                            <h3 class="text-xl font-bold">{{ $module['name'] }}</h3>
                        </div>

                        <!-- Module Body -->
                        <div class="p-6 space-y-4">
                            <!-- Author -->
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                by <span class="font-medium">{{ $module['author'] ?? 'Unknown' }}</span>
                            </p>

                            <!-- Description -->
                            <p class="text-sm text-gray-700 dark:text-gray-300 line-clamp-2">
                                {{ $module['description'] }}
                            </p>

                            <!-- Rating & Stats -->
                            <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                                @if(isset($module['rating']))
                                    <span>⭐ {{ number_format($module['rating'], 1) }}</span>
                                @endif
                                @if(isset($module['downloads']))
                                    <span>{{ number_format($module['downloads']) }} installs</span>
                                @endif
                                @if(isset($module['version']))
                                    <span class="text-xs">v{{ $module['version'] }}</span>
                                @endif
                            </div>

                            <!-- Tags -->
                            @if(isset($module['tags']) && !empty($module['tags']))
                                <div class="flex flex-wrap gap-2 pt-2">
                                    @foreach(array_slice($module['tags'], 0, 3) as $tag)
                                        <x-filament::badge>
                                            {{ $tag }}
                                        </x-filament::badge>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <!-- Footer Actions -->
                        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800 flex gap-2">
                            <x-filament::button
                                wire:click="installModule('{{ $module['id'] }}')"
                                class="flex-1"
                            >
                                Install
                            </x-filament::button>
                            <x-filament::icon-button
                                icon="heroicon-o-information-circle"
                            />
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
