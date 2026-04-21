<x-filament-panels::page>

    <x-filament::section>
        <x-slot name="heading">
            Brand
        </x-slot>
        {{ $this->brandForm }}
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">
            Panels
        </x-slot>
        {{ $this->panelsForm }}
    </x-filament::section>

</x-filament-panels::page>
