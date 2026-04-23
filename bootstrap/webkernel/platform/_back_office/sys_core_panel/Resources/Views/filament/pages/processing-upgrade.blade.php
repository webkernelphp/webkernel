@php
    $isDark = match(\Filament\Support\Facades\FilamentColor::getTheme()) {
        'dark' => true,
        default => false,
    };
    $logo = webkernelBrandingUrl($isDark ? 'webkernel-logo-dark' : 'webkernel-logo-light');
@endphp

<div class="flex flex-col items-center justify-center min-h-screen bg-white dark:bg-slate-950 px-4">
    <div class="max-w-md w-full space-y-8">
        <!-- Logo -->
        <div class="flex justify-center">
            @if($logo)
                <img src="{{ $logo }}" alt="Webkernel" class="h-16 w-auto">
            @else
                <div class="h-16 w-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                    <span class="text-white font-bold text-xl">WK</span>
                </div>
            @endif
        </div>

        <!-- Progress Section -->
        <div class="space-y-6">
            <!-- Progress Bar -->
            <div class="space-y-2">
                <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2 overflow-hidden">
                    <div
                        class="h-full bg-gradient-to-r from-blue-500 to-purple-600 rounded-full transition-all duration-500"
                        style="width: {{ $this->getProgressPercentage() }}%"
                        wire:poll.500ms="updateProgress"
                    ></div>
                </div>
            </div>

            <!-- Status Message -->
            <div class="text-center space-y-2">
                <p class="text-sm font-semibold text-slate-900 dark:text-white">
                    {{ $this->updateStatus ?: 'Preparing upgrade…' }}
                </p>
                @if($this->updateError)
                    <p class="text-sm text-red-600 dark:text-red-400 font-medium">
                        {{ $this->updateError }}
                    </p>
                @endif
            </div>
        </div>

        <!-- Steps -->
        <div class="space-y-3">
            @php
                $steps = [
                    ['key' => 'backup', 'label' => 'Creating backup'],
                    ['key' => 'download', 'label' => 'Downloading release'],
                    ['key' => 'extract', 'label' => 'Extracting files'],
                    ['key' => 'verify', 'label' => 'Verifying integrity'],
                    ['key' => 'swap', 'label' => 'Swapping kernel'],
                    ['key' => 'cleanup', 'label' => 'Cleaning up'],
                ];
            @endphp

            @foreach($steps as $step)
                <div class="flex items-center space-x-3">
                    @if(str_contains($this->updateStatus, $step['label']))
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-6 w-6 rounded-full bg-blue-500 animate-spin">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                    @elseif(!empty($this->updateError))
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-6 w-6 rounded-full bg-red-500">
                                <svg class="h-4 w-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                    @else
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-6 w-6 rounded-full bg-slate-300 dark:bg-slate-600">
                                <svg class="h-4 w-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                </svg>
                            </div>
                        </div>
                    @endif
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                        {{ $step['label'] }}
                    </span>
                </div>
            @endforeach
        </div>

        <!-- Warning Note -->
        <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900 rounded-lg p-3">
            <p class="text-xs text-amber-800 dark:text-amber-200">
                <strong>Do not close this window.</strong> A backup will be created before any changes are made. You can rollback if something goes wrong.
            </p>
        </div>
    </div>
</div>
