<?php

$ffi = FFI::cdef("
int printf(const char *format, ...);
", "libc.so.6");

/** @disregard */
$ffi->printf("Hello FFI\n");
