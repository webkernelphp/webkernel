<x-filament-panels::page>
    <form wire:submit="saveSettings">
        {{ $this->form }}

        <div class="mt-6 flex justify-end gap-3">
            <x-filament::button
                type="submit"
                icon="heroicon-m-check"
                color="primary"
            >
                Save Settings
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
