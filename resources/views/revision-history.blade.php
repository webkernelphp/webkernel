<div class="space-y-4">
    @if($revisions->isEmpty())
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 mx-auto mb-3 opacity-50">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
            <p class="text-sm">{{ __('layup::builder.no_revisions') }}</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($revisions as $revision)
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-750 transition">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-gray-400">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $revision->created_at->format('M j, Y g:i A') }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                ({{ $revision->created_at->diffForHumans() }})
                            </span>
                        </div>
                        
                        @if($revision->note)
                            <p class="text-xs text-gray-600 dark:text-gray-400 truncate">
                                {{ $revision->note }}
                            </p>
                        @endif
                        
                        @if($revision->author)
                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                By: {{ $revision->author }}
                            </p>
                        @endif
                        
                        <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                            {{ count($revision->content['rows'] ?? []) }} row(s)
                        </div>
                    </div>
                    
                    <button
                        wire:click="restoreRevision({{ $revision->id }})"
                        wire:loading.attr="disabled"
                        wire:target="restoreRevision({{ $revision->id }})"
                        class="ml-4 px-3 py-1.5 text-xs font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-md transition disabled:opacity-50 disabled:cursor-not-allowed"
                        onclick="if(!confirm('{{ __('layup::builder.restore_confirm') }}')) { event.stopPropagation(); return false; }"
                    >
                        <span wire:loading.remove wire:target="restoreRevision({{ $revision->id }})">
                            {{ __('layup::builder.restore') }}
                        </span>
                        <span wire:loading wire:target="restoreRevision({{ $revision->id }})">
                            {{ __('layup::builder.restoring') }}
                        </span>
                    </button>
                </div>
            @endforeach
        </div>
        
        @if($revisions->count() >= 50)
            <div class="text-center text-xs text-gray-500 dark:text-gray-400 pt-2">
                {{ __('layup::builder.showing_last_revisions') }}
            </div>
        @endif
    @endif
</div>
