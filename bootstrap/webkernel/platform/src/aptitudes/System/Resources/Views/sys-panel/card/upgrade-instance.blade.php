<div>
   <style>
      .wk-logo-box {
      height:120px;
      width:120px;
      border-radius:2rem;
      /* border:1px solid rgba(255,255,255,0.5); */
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
   <div style="display:flex;align-items:center;gap:1rem;padding:12px 16px;flex-wrap:wrap;">
      <div style="width:36px;height:36px;border-radius:50%;background:#e5e7eb;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;">
         AB
      </div>
      <div style="min-width:0;flex:1;">
         <div style="font-size:0.875rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
            Alice Brown
         </div>
         <div style="font-size:0.75rem;opacity:.55;">
            @alicebrown
         </div>
      </div>
   </div>
   <div style="padding:12px 16px;">
      <x-filament::section compact secondary>
         <div style="display:flex;justify-content:center;align-items:center;width:100%;margin-bottom:1.3rem;">
            <div class="wk-logo-box">
               <img
                  alt="Numerimondes logo"
                  src="/app-icon.png"
                  style="height:100%;width:100%;object-fit:cover;"
                  />
            </div>
         </div>
         <div style="display:flex;flex-direction:column;gap:2rem;">
            <div style="text-align:center;">
               <h1 style="font-size:1.875rem;font-weight:700;">
                  Webkernel {{ method_exists(app(), 'webkernelVersion') ? app()->webkernelVersion() : 'dev' }}
               </h1>
               <p style="font-size:0.875rem;color:#6b7280;">
                  Released February 27, 2026
               </p>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1.5rem;">
               <x-filament::fieldset style="padding-top:.2rem!important;padding-bottom:.9rem!important;">
                  <x-slot name="label">
                     Linux (Stable)
                  </x-slot>
                  <p style="font-size:0.875rem;color:#6b7280;padding-bottom:.4rem!important;">
                     Requires macOS 10.15 or later
                  </p>
                  <div style="width:100%;">
                     <x-filament::button
                        tag="a"
                        href="https://webkernelphp.com/download/"
                        style="display:block;width:100%;text-align:center;border-radius:50px;"
                        >
                        Update this instance
                     </x-filament::button>
                  </div>
                  <div style="display:flex;flex-wrap:wrap;gap:0.5rem;padding-top:.6rem!important;">
                     <x-filament::link tag="a" href="https://zed.dev/changelog" style="flex:1;min-width:150px;text-align:center;">
                        View changelog
                     </x-filament::link>
                     <x-filament::link tag="a" href="https://zed.dev/linux-install" style="flex:1;min-width:150px;text-align:center;">
                        Other methods
                     </x-filament::link>
                  </div>
               </x-filament::fieldset>
            </div>
            <div style="text-align:center;font-size:0.75rem;color:#6b7280;">
               By downloading and using Webkernel, you agree to its terms and conditions.
            </div>
         </div>
      </x-filament::section>
   </div>
</div>
