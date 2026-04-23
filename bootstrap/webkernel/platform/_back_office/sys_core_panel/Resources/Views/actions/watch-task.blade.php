<div class="space-y-4" wire:poll.500ms="$refresh">
    <div class="grid grid-cols-4 gap-4">
        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
            <p class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Status</p>
            <div class="mt-2 flex items-center gap-2">
                @if ($getRecord()->status === 'pending')
                    <div class="w-2 h-2 bg-yellow-500 rounded-full animate-pulse"></div>
                    <span class="text-sm font-medium text-yellow-700 dark:text-yellow-300">Pending</span>
                @elseif ($getRecord()->status === 'running')
                    <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                    <span class="text-sm font-medium text-blue-700 dark:text-blue-300">Running</span>
                @elseif ($getRecord()->status === 'completed')
                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                    <span class="text-sm font-medium text-green-700 dark:text-green-300">Completed</span>
                @else
                    <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                    <span class="text-sm font-medium text-red-700 dark:text-red-300">{{ ucfirst($getRecord()->status) }}</span>
                @endif
            </div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
            <p class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Duration</p>
            <p class="mt-2 text-sm font-semibold">{{ $getRecord()->getDurationFormatted() }}</p>
        </div>
        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
            <p class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Type</p>
            <p class="mt-2 text-sm">{{ str($getRecord()->type)->replace('_', ' ')->title() }}</p>
        </div>
        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
            <p class="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase">Started</p>
            <p class="mt-2 text-sm">{{ $getRecord()->started_at?->format('H:i:s') ?? '—' }}</p>
        </div>
    </div>

    <div class="border border-gray-700 rounded-lg overflow-hidden bg-gray-950">
        <div class="bg-gray-900 px-4 py-2 border-b border-gray-700 flex items-center justify-between">
            <span class="text-xs text-gray-400 font-mono"><span class="text-green-400">$</span> {{ $getRecord()->type }}</span>
            @if ($getRecord()->status === 'pending' || $getRecord()->status === 'running')
                <span class="text-xs text-gray-500 animate-pulse">● updating...</span>
            @endif
        </div>
        <div class="overflow-auto max-h-96 bg-gray-950">
            <pre class="p-4 text-green-400 font-mono text-sm whitespace-pre-wrap break-words">{{ $getRecord()->output ?? 'Waiting for output...' }}</pre>
        </div>
    </div>

    @if ($getRecord()->error)
        <div class="border border-red-800 rounded-lg bg-red-950/30 overflow-hidden">
            <div class="bg-red-900/50 px-4 py-2 border-b border-red-800 text-xs text-red-400 font-mono">✕ Error</div>
            <div class="overflow-auto max-h-48 p-4">
                <pre class="text-red-300 font-mono text-sm whitespace-pre-wrap break-words">{{ $getRecord()->error }}</pre>
            </div>
        </div>
    @endif
</div>
