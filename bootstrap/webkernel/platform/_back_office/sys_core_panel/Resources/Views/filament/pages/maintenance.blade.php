<x-filament-panels::page>
   <x-webkernel::dashboard.assets
      filament-tabs="false"
      custom-scrollbars="false"
      dark-section-fix="false"/>

   <style>
      .wk-logo-box {
         height:120px;
         width:120px;
         border-radius:2rem;
         border:2px solid rgba(255,255,255,0.5);
         overflow:hidden;
         display:flex;
         align-items:center;
         justify-content:center;
         position:relative;
         z-index:2;

         box-shadow:
            0 6px 18px rgba(0,0,0,0.15),
            0 2px 6px rgba(0,0,0,0.08);

         transition:
            transform 0.35s cubic-bezier(.4,0,.2,1),
            box-shadow 0.35s cubic-bezier(.4,0,.2,1);
      }

      .wk-logo-box:hover {
         transform: translateY(-6px) scale(1.03);
         box-shadow:
            0 14px 35px rgba(0,0,0,0.25),
            0 28px 65px rgba(0,0,0,0.35),
            inset 0 0 25px rgba(255,255,255,0.12);
      }
   </style>

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
