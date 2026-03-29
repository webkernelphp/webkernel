<x-filament-panels::page>

   <x-webkernel::dashboard.assets
      filament-tabs="false"
      custom-scrollbars="false"
      dark-section-fix="false"/>

   <x-webkernel::dashboard columns="2" grid="12">

      {{-- Left column --}}

      <x-webkernel::dashboard.column span="3" class="col-span-12 md:col-span-3">
          @include('webkernel-system::sys-panel.card.upgrade-instance')
      </x-webkernel::dashboard.column>


      {{-- Right column --}}
      <x-webkernel::dashboard.column span="9" class="col-span-12 md:col-span-9">

         <div
            x-data="{}"
            x-on:wk-toast.window="
               $dispatch('filament-notification', {
                  type: $event.detail.type,
                  heading: $event.detail.message
               })
            "
         ></div>

         @if(config('broadcasting.default') !== 'log' && config('broadcasting.default') !== 'null')
         <div
            x-data="{
               init() {
                  if (typeof Echo === 'undefined') return;
                  Echo.private('{{ WEBKERNEL_WS_CHANNEL_SYSTEM }}')
                     .listen('.SystemAction', (e) => {
                        $dispatch('filament-notification', {
                           type: 'info',
                           heading: 'System event: ' + e.action,
                           body: e.performed_by ? 'Performed by: ' + e.performed_by : ''
                        });
                     });
               },
               destroy() {
                  if (typeof Echo !== 'undefined') {
                     Echo.leave('{{ WEBKERNEL_WS_CHANNEL_SYSTEM }}');
                  }
               }
            }"
         ></div>
         @endif

         <div style="padding:1.25rem;">
             {{ $this->form }}
         </div>

      </x-webkernel::dashboard.column>

   </x-webkernel::dashboard>
</x-filament-panels::page>
