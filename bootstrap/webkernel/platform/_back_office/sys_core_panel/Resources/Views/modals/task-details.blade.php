<div class="space-y-6">
    <!-- Status & Duration -->
    <div class="grid grid-cols-2 gap-4">
        <div>
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Status</p>
            <div class="mt-2">
                <span @class([
                    'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium',
                    'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300' => $record->status === 'pending',
                    'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300' => $record->status === 'running',
                    'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' => $record->status === 'completed',
                    'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300' => $record->status === 'failed',
                    'bg-gray-100 dark:bg-gray-900/30 text-gray-800 dark:text-gray-300' => $record->status === 'cancelled',
                ])>
                    {{ ucfirst($record->status) }}
                </span>
            </div>
        </div>
        <div>
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Duration</p>
            <p class="mt-2 text-lg font-semibold text-gray-900 dark:text-gray-100">
                {{ $record->getDurationFormatted() }}
            </p>
        </div>
    </div>

    <!-- Timestamps -->
    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        @if ($record->started_at)
            <div>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Started</p>
                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                    {{ $record->started_at->format('M d, Y H:i:s') }}
                </p>
            </div>
        @endif
        @if ($record->completed_at)
            <div>
                <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Completed</p>
                <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">
                    {{ $record->completed_at->format('M d, Y H:i:s') }}
                </p>
            </div>
        @endif
    </div>

    <!-- Task Type & Details -->
    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Task Type</p>
        <p class="mt-1 text-sm font-mono text-gray-700 dark:text-gray-300">
            {{ str($record->type)->replace('_', ' ')->title() }}
        </p>
        @if ($record->payload)
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mt-3">Payload</p>
            <pre class="mt-1 text-xs font-mono text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900 p-2 rounded overflow-x-auto">{{ json_encode($record->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        @endif
    </div>

    <!-- Output Terminal -->
    @if ($output || $error)
        <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
            @if ($output)
                <div>
                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Output</p>
                    <div class="bg-gray-900 dark:bg-black rounded p-4 overflow-auto max-h-96 border border-gray-700">
                        <pre class="text-green-400 font-mono text-xs whitespace-pre-wrap break-words leading-relaxed">{{ $output }}</pre>
                    </div>
                </div>
            @endif

            @if ($error)
                <div @class(['mt-4' => $output])>
                    <p class="text-xs font-semibold text-red-600 dark:text-red-400 uppercase tracking-wide mb-2">Error</p>
                    <div class="bg-red-950 dark:bg-red-950/50 rounded p-4 overflow-auto max-h-96 border border-red-800">
                        <pre class="text-red-300 font-mono text-xs whitespace-pre-wrap break-words leading-relaxed">{{ $error }}</pre>
                    </div>
                </div>
            @endif
        </div>
    @endif

    @if (!$output && !$error)
        <div class="pt-4 border-t border-gray-200 dark:border-gray-700 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                No output available
                @if ($record->status === 'running')
                    <span class="block text-xs mt-1 animate-pulse">Task is currently running...</span>
                @endif
            </p>
        </div>
    @endif
</div>
