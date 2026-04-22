@props(['module'])

<div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden hover:shadow-md transition-shadow">
    <div class="p-6">
        <!-- Header -->
        <div class="mb-4">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
                {{ $module['name'] }}
            </h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                by {{ $module['author'] ?? 'Unknown' }}
            </p>
        </div>

        <!-- Description -->
        <p class="text-sm text-slate-600 dark:text-slate-300 mb-4 line-clamp-2">
            {{ $module['description'] }}
        </p>

        <!-- Meta Info -->
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; font-size: 0.75rem; color: #6b7280;">
            @if(isset($module['rating']))
                <div style="display: flex; align-items: center; gap: 0.25rem;">
                    <svg style="width: 1rem; height: 1rem; color: #fbbf24;" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    <span>{{ number_format($module['rating'], 1) }}</span>
                </div>
            @endif

            @if(isset($module['downloads']))
                <div>
                    {{ number_format($module['downloads']) }} downloads
                </div>
            @endif

            @if(isset($module['version']))
                <div style="color: #4b5563;">
                    v{{ $module['version'] }}
                </div>
            @endif
        </div>

        <!-- Tags -->
        @if(isset($module['tags']) && !empty($module['tags']))
            <div class="flex flex-wrap gap-2 mb-4">
                @foreach($module['tags'] as $tag)
                    <span class="inline-block px-2 py-1 text-xs bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded">
                        {{ $tag }}
                    </span>
                @endforeach
            </div>
        @endif

        <!-- Install Button -->
        <button
            type="button"
            wire:click="$emit('installModule', '{{ $module['id'] }}')"
            class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2 px-4 rounded-lg transition-colors"
        >
            Install Module
        </button>
    </div>
</div>
