<?php declare(strict_types=1);

use FFI;
final class FfiModuleManager
{
    private FFI $ffi;

    public function __construct()
    {
        $this->ffi = FFI::cdef(
            file_get_contents(base_path('bootstrap/webkernel/runtime/native/ffi/webkernel.h')),
            base_path('bootstrap/webkernel/runtime/native/lib/libwebkernel.so')
        );
    }

 //   public function load(string $path): void
 //   {
 //       $this->ffi->webkernel_load_module($path);
 //   }
 //
 //   public function unload(string $module): void
 //   {
 //       $this->ffi->webkernel_unload_module($module);
 //   }
}
