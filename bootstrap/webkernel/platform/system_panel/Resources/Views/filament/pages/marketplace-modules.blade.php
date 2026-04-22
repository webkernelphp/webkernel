<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Search & Filters -->
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm border border-slate-200 dark:border-slate-800 p-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Search -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Search</label>
                    <input
                        type="text"
                        wire:model.live="searchQuery"
                        placeholder="Search modules, tags, authors..."
                        class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                </div>

                <!-- Sort -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Sort By</label>
                    <select
                        wire:model.live="sortBy"
                        class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option value="name">Name</option>
                        <option value="recent">Recently Released</option>
                        <option value="popular">Most Downloaded</option>
                    </select>
                </div>

                <!-- Status -->
                <div class="flex items-end">
                    @if($isLoading)
                        <div class="text-sm text-slate-600 dark:text-slate-400">Loading modules...</div>
                    @else
                        <div class="text-sm text-slate-600 dark:text-slate-400">
                            {{ count($this->filteredModules()) }} module{{ count($this->filteredModules()) !== 1 ? 's' : '' }} found
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Module Cards Grid -->
        @if($isLoading)
            <div style="display: flex; justify-content: center; align-items: center; padding: 3rem 0;">
                <div style="text-align: center;">
                    <div style="border: 2px solid #3b82f6; border-top-color: transparent; border-radius: 50%; width: 2.5rem; height: 2.5rem; animation: spin 0.6s linear infinite; margin: 0 auto 1rem;"></div>
                    <p style="color: #9ca3af;">Loading marketplace...</p>
                </div>
            </div>
        @elseif(empty($this->filteredModules()))
            <div style="text-align: center; padding: 3rem 1.25rem; background-color: #f9fafb; border-radius: 0.5rem; border: 1px solid #e5e7eb;">
                <svg style="width: 3rem; height: 3rem; color: #9ca3af; margin: 0 auto 1rem; display: block;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <p style="color: #9ca3af;">No modules found</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($this->filteredModules() as $module)
                    <x-webkernel::module-card :module="$module" />
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
