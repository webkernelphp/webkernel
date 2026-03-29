# Understanding PHP-FPM, FFI, and Configuration Changes

This document explains how PHP-FPM handles FFI (Foreign Function Interface), why configuration changes require a server restart, and how to ensure OPcache is enabled for optimal behavior.

---

## Horizontal Visualization of PHP-FPM Processing

Requests flow horizontally through the system. The PHP process acts like a production line (factory) that transforms incoming requests into responses. Configurations are fixed at startup.

```

[Incoming HTTP Requests] --->  PHP-FPM PROCESS IN MEMORY  --->  [Client Responses]

┌───────────────────────────────┐   ┌──────────────────────────────────────────┐   ┌─────────────────────────────┐
│ HTTP REQUESTS                 │ → │ PHP-FPM PROCESS IN MEMORY                  │ → │ CLIENT RESPONSES            │
│ Req1, Req2, Req3 ...          │   │------------------------------------------│   │ Resp1, Resp2, Resp3 ...    │
│ Enter the "factory"           │   │ CONFIGS (fixed at startup)                 │   └─────────────────────────────┘
└───────────────────────────────┘   │ - ffi.enable = preload                     │
│ - memory_limit = 512M                      │
│------------------------------------------│
│ WEBKERNEL FUNCTIONS / MACHINES            │
│ - wk_init()                                │
│ - wk_route()                               │
│ - wk_render()                              │
│------------------------------------------│
│ PASSENGERS (REQUESTS BEING PROCESSED)      │
│ [Req1] [Req2] [Req3] ...                   │
└──────────────────────────────────────────┘

Changing ffi.enable:
The PHP-FPM process is already running; configurations are fixed.
To apply a new configuration: restart the server → a new process is created with the updated configuration.

````

---

## Step-by-Step Guide for Users

### 1. Check Current FFI Configuration

Run the following command to see the current FFI setting:

```bash
php -i | grep ffi.enable
````

This will show `preload`, `true`, or `false`.
FFI is **read at process startup**, so changes require a restart.

---

### 2. Modify `php.ini` or `.user.ini`

To enable FFI globally:

```ini
ffi.enable = true
```

* `preload` = only preloaded extensions can use FFI
* `true` = full FFI enabled

Save the file.

---

### 3. Verify OPcache

FFI works best when OPcache is active, especially with Webkernel functions.

```bash
php -i | grep "OPcache"
```

If OPcache is not active, add to `php.ini`:

```ini
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.revalidate_freq=2
```

* `enable_cli=1` ensures OPcache works in CLI scripts.

---

### 4. Restart PHP Server

To apply new FFI and OPcache settings:

* **PHP-FPM**:

```bash
sudo systemctl restart php8.2-fpm
```

* **Apache + mod_php**:

```bash
sudo systemctl restart apache2
```

* **CLI scripts**: just restart the script; no server restart needed.

> Restarting creates a **new PHP-FPM process in memory** with the updated configuration. All new requests will pass through this new process.

---

### 5. Test FFI

Create a small test script:

```php
<?php
var_dump(FFI::cdef("int printf(const char *fmt, ...);"));
```

* Should return an FFI object without errors.
* Verify Webkernel native functions (`wk_init()`, `wk_route()`, `wk_render()`) are available.

---

## Key Notes for Users

* PHP-FPM reads `ffi.enable` **once at process startup**. Changes require a restart.
* OPcache should be enabled for stability and performance.
* Requests already being processed by the current PHP-FPM process **cannot use new FFI settings**; only new requests after restart will.
* This flow ensures consistent memory management and configuration stability.
