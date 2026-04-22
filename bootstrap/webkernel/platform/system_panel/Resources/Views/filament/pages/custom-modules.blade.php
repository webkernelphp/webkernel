<x-filament-panels::page>
    {{ $this->form }}

    @if(!empty($installStatus))
        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-800 rounded-lg">
            <p class="text-sm text-blue-700 dark:text-blue-300">{{ $installStatus }}</p>
        </div>
    @endif

    @if(!empty($installError))
        <div class="mt-6 p-4 bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-800 rounded-lg">
            <p class="text-sm text-red-700 dark:text-red-300">{{ $installError }}</p>
        </div>
    @endif

    @if(!empty($installPath))
        <div class="mt-6 p-4 bg-emerald-50 dark:bg-emerald-900 border border-emerald-200 dark:border-emerald-800 rounded-lg">
            <p class="text-sm text-emerald-700 dark:text-emerald-300">Installed at: {{ $installPath }}</p>
        </div>
    @endif
</x-filament-panels::page>
