@php
    $steps = [
        'backup' => 'Creating backup',
        'download' => 'Downloading release',
        'extract' => 'Extracting files',
        'verify' => 'Verifying integrity',
        'swap' => 'Swapping kernel',
        'cleanup' => 'Cleaning up',
    ];
@endphp

<x-filament-panels::page>
<style>
    .process-upgrade-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 100%);
        color: #ffffff;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        padding: 2rem;
    }

    .process-upgrade-content {
        width: 100%;
        max-width: 500px;
        text-align: center;
    }

    .process-upgrade-logos {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1.5rem;
        margin-bottom: 3rem;
    }

    .process-upgrade-logo {
        width: 80px;
        height: 80px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .process-upgrade-logo img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 8px;
    }

    .process-upgrade-logo.secondary {
        width: 60px;
        height: 60px;
        opacity: 0.8;
    }

    .process-upgrade-progress-section {
        margin-bottom: 3rem;
    }

    .process-upgrade-title {
        font-size: 1.75rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        letter-spacing: -0.02em;
    }

    .process-upgrade-subtitle {
        font-size: 0.875rem;
        color: rgba(255, 255, 255, 0.6);
        margin-bottom: 2rem;
    }

    .process-upgrade-progress-bar {
        width: 100%;
        height: 3px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 2px;
        overflow: hidden;
        margin-bottom: 1rem;
    }

    .process-upgrade-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #3b82f6 0%, #8b5cf6 100%);
        border-radius: 2px;
        transition: width 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        width: var(--progress-width, 0%);
    }

    .process-upgrade-status-text {
        font-size: 0.9375rem;
        color: rgba(255, 255, 255, 0.9);
        font-weight: 500;
        letter-spacing: -0.01em;
    }

    .process-upgrade-steps {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin-bottom: 2rem;
    }

    .process-upgrade-step {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        border-radius: 6px;
        transition: background-color 0.2s;
    }

    .process-upgrade-step.pending {
        color: rgba(255, 255, 255, 0.5);
    }

    .process-upgrade-step.active {
        color: #3b82f6;
        background: rgba(59, 130, 246, 0.1);
    }

    .process-upgrade-step.completed {
        color: rgba(255, 255, 255, 0.7);
    }

    .process-upgrade-step-icon {
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .process-upgrade-step.active .process-upgrade-step-icon {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .process-upgrade-step-label {
        font-size: 0.875rem;
        text-align: left;
    }

    .process-upgrade-warning {
        background: rgba(217, 119, 6, 0.1);
        border: 1px solid rgba(217, 119, 6, 0.3);
        border-radius: 6px;
        padding: 0.875rem;
        font-size: 0.8125rem;
        line-height: 1.6;
        color: rgba(255, 255, 255, 0.8);
    }

    .process-upgrade-error {
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.3);
        border-radius: 6px;
        padding: 0.875rem;
        font-size: 0.875rem;
        line-height: 1.5;
        color: rgba(239, 68, 68, 0.8);
        margin-bottom: 1.5rem;
        font-family: 'Courier New', monospace;
    }
</style>

<div class="process-upgrade-container" wire:poll.500ms="updateProgress">
    <div class="process-upgrade-content">
        <!-- Logos -->
        <div class="process-upgrade-logos">
            <div class="process-upgrade-logo">
                @if($primaryLogo)
                    <img src="{{ $primaryLogo }}" alt="Primary Logo" />
                @else
                    <span style="font-size: 2rem;">⚙️</span>
                @endif
            </div>
            @if($secondaryLogo)
                <div class="process-upgrade-logo secondary">
                    <img src="{{ $secondaryLogo }}" alt="Secondary Logo" />
                </div>
            @endif
        </div>

        <!-- Progress Section -->
        <div class="process-upgrade-progress-section">
            <h1 class="process-upgrade-title">{{ $operationTitle }}</h1>
            <p class="process-upgrade-subtitle">{{ $status ?: 'Initializing…' }}</p>

            <div class="process-upgrade-progress-bar">
                <div class="process-upgrade-progress-fill" style="--progress-width: {{ $progressPercent }}%"></div>
            </div>
            <p class="process-upgrade-status-text">{{ $progressPercent }}%</p>
        </div>

        <!-- Steps -->
        <div class="process-upgrade-steps">
            @php
                $stepProgress = [
                    'backup' => 15,
                    'download' => 35,
                    'extract' => 50,
                    'verify' => 65,
                    'swap' => 80,
                    'cleanup' => 90,
                ];
            @endphp

            @foreach($steps as $key => $label)
                @php
                    $stepPercent = $stepProgress[$key] ?? 0;
                    if ($progressPercent >= $stepPercent + 15) {
                        $stepClass = 'completed';
                        $icon = '✓';
                    } elseif ($progressPercent >= $stepPercent) {
                        $stepClass = 'active';
                        $icon = '⟳';
                    } else {
                        $stepClass = 'pending';
                        $icon = '◯';
                    }
                @endphp
                <div class="process-upgrade-step {{ $stepClass }}">
                    <div class="process-upgrade-step-icon">{{ $icon }}</div>
                    <div class="process-upgrade-step-label">{{ $label }}</div>
                </div>
            @endforeach
        </div>

        <!-- Error Display -->
        @if($error)
            <div class="process-upgrade-error">
                <strong>Error:</strong><br>{{ $error }}
            </div>
        @endif

        <!-- Warning -->
        <div class="process-upgrade-warning">
            ⚠️ <strong>Do not close this window.</strong> The system will resume automatically once the operation completes.
        </div>
    </div>
</div>
</x-filament-panels::page>
