<div class="space-y-6">
    <!-- Task Metadata Table -->
    <div class="fi-section">
        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="w-full">
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr class="bg-gray-50 dark:bg-gray-900/50">
                        <td class="px-4 py-3 text-sm font-semibold text-gray-700 dark:text-gray-300 w-32">Status</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
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
                        </td>
                    </tr>
                    <tr>
                        <td class="px-4 py-3 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50">Duration</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $record->getDurationFormatted() }}</td>
                    </tr>
                    <tr class="bg-gray-50 dark:bg-gray-900/50">
                        <td class="px-4 py-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Type</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                            <span class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900/30 px-2.5 py-0.5 text-sm font-medium text-blue-800 dark:text-blue-300">
                                {{ str($record->type)->replace('_', ' ')->title() }}
                            </span>
                        </td>
                    </tr>
                    @if ($record->started_at)
                        <tr>
                            <td class="px-4 py-3 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-900/50">Started</td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $record->started_at->format('M d, Y H:i:s') }}</td>
                        </tr>
                    @endif
                    @if ($record->completed_at)
                        <tr class="bg-gray-50 dark:bg-gray-900/50">
                            <td class="px-4 py-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Completed</td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">{{ $record->completed_at->format('M d, Y H:i:s') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payload -->
    @if ($record->payload)
        <div class="fi-section">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Payload</h3>
            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-4 overflow-x-auto">
                <pre class="text-xs font-mono text-gray-700 dark:text-gray-300">{{ json_encode($record->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>
    @endif

    <!-- Output Terminal -->
    @if ($output || $error)
        <div class="fi-section">
            @if ($output)
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Output</h3>
                    <div class="bg-gray-900 dark:bg-black rounded-lg border border-gray-700 p-4 overflow-auto max-h-80">
                        <pre class="text-green-400 font-mono text-xs whitespace-pre-wrap break-words leading-relaxed">{{ $output }}</pre>
                    </div>
                </div>
            @endif

            @if ($error)
                <div @class(['mt-4' => $output])>
                    <h3 class="text-sm font-semibold text-red-600 dark:text-red-400 mb-2">Error</h3>
                    <div class="bg-red-950 dark:bg-red-950/50 rounded-lg border border-red-800 p-4 overflow-auto max-h-80">
                        <pre class="text-red-300 font-mono text-xs whitespace-pre-wrap break-words leading-relaxed">{{ $error }}</pre>
                    </div>
                </div>
            @endif
        </div>
    @else
        <div class="fi-section text-center py-8">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                No output available
                @if ($record->status === 'running')
                    <span class="block text-xs mt-2 animate-pulse">Task is currently running...</span>
                @endif
            </p>
        </div>
    @endif
</div>
