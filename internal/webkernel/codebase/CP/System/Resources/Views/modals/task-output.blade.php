<div class="space-y-4">
    @if ($output)
        <div>
            <h4 class="text-sm font-semibold mb-2">Output</h4>
            <div class="bg-gray-100 dark:bg-gray-900 rounded p-3 text-xs font-mono overflow-auto max-h-96 text-gray-900 dark:text-gray-100 whitespace-pre-wrap break-words">
                {{ $output }}
            </div>
        </div>
    @endif

    @if ($error)
        <div>
            <h4 class="text-sm font-semibold mb-2 text-red-600 dark:text-red-400">Error</h4>
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded p-3 text-xs font-mono overflow-auto max-h-96 text-red-800 dark:text-red-300 whitespace-pre-wrap break-words">
                {{ $error }}
            </div>
        </div>
    @endif

    @if (!$output && !$error)
        <div class="text-gray-500 dark:text-gray-400 text-sm">
            No output available
        </div>
    @endif
</div>
