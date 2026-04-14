# Webkernel Module Authoring Guide

This document is the reference for writing Webkernel modules: first-party (Numerimondes), second-party (your organisation), or third-party (community). It covers scaffolding, manifest declaration, FFI integration, signing, and submission.

Modules that do not conform to this guide will be refused by the registry.

---

## 1. Prerequisites

- PHP 8.4+
- Laravel 12+
- Webkernel 1.0.0+
- `webkernel:make-module` CLI command available (Webkernel SDK installed)
- For FFI modules: Linux x86_64, GCC 12+ or Clang 15+, Ed25519 signing key pair registered with the Webkernel key store

---

## 2. Scaffolding

```bash
php artisan webkernel:make-module
```

The generator prompts for registry, vendor slug, module slug, label, description, version, party, author, compatibility constraints, license, and structure preset. It produces a ready-to-use skeleton at:

```
modules/<registry>/<vendor>/<slug>/
```

---

## 3. Module Manifest (`module.php`)

Every module must have a `module.php` at its root. This is the single source of truth: identity, slots, FFI, dependencies, and compatibility are all declared here.

### Full Reference

```php
<?php declare(strict_types=1);
return [
    /*-- IDENTITY ------------------------------------------------------------------*/
    'id'          => 'webkernelphp-com::acme/payments',
    'label'       => 'Payments',
    'description' => 'One-line description.',
    'version'     => '1.0.0',
    'active'      => true,

    /*-- NAMESPACE -----------------------------------------------------------------*/
    'namespace'   => 'WebModule\AcmePayments',

    /*-- REGISTRY ------------------------------------------------------------------*/
    'registry'    => 'webkernelphp.com',
    'vendor'      => 'acme',
    'slug'        => 'payments',

    /*-- PARTY ---------------------------------------------------------------------*/
    'party'       => 'second',                  // first | second | third

    /*-- PROVIDERS -----------------------------------------------------------------*/
    'providers'   => [
        WebModule\AcmePayments\Providers\PaymentsServiceProvider::class,
    ],

    /*-- HELPERS -------------------------------------------------------------------*/
    'helpers'       => [],
    'helpers_paths' => [],

    /*-- ROUTES --------------------------------------------------------------------*/
    'route_groups' => [
        'web' => ['routes/web.php'],
        'api' => ['routes/api.php'],
    ],
    'route_paths'  => [],

    /*-- CONFIG --------------------------------------------------------------------*/
    'config_paths' => ['config'],

    /*-- VIEWS ---------------------------------------------------------------------*/
    'view_namespaces' => [
        'acme-payments' => 'resources/views',
    ],

    /*-- TRANSLATIONS --------------------------------------------------------------*/
    'lang_paths' => [
        'acme-payments' => 'lang',
    ],

    /*-- MIGRATIONS ----------------------------------------------------------------*/
    'migration_paths' => ['database/migrations'],

    /*-- SEEDERS -------------------------------------------------------------------*/
    'seeder_paths' => ['database/seeders'],

    /*-- COMMANDS ------------------------------------------------------------------*/
    'command_paths' => ['src/Console'],

    /*-- LIVEWIRE ------------------------------------------------------------------*/
    'livewire_paths' => ['src/Livewire'],

    /*-- FILAMENT ------------------------------------------------------------------*/
    'filament_paths' => ['src/Filament'],

    /*-- ASSETS --------------------------------------------------------------------*/
    'asset_paths' => ['resources/assets'],

    /*-- BLAZE ---------------------------------------------------------------------*/
    'blade_to_optimize_paths' => [
        ['path' => 'resources/views', 'compile' => true, 'fold' => false, 'memo' => false, 'safe' => [], 'unsafe' => []],
    ],

    /*-- FFI -----------------------------------------------------------------------*/
    // Only include this block if the module ships native code.
    'ffi' => [
        'enabled' => true,
        'modules' => [
            [
                'name'        => 'webkernelphp-com::acme/payments',
                'lib'         => 'native/lib/module_payments.so',
                'header'      => 'native/ffi/module_payments.h',
                'abi_version' => 1,
            ],
        ],
        'access' => [
            'scope'          => 'scoped',
            'required_group' => 'app-owner',
            'functions'      => [
                'payments_process' => ['required_group' => 'app-owner'],
                'payments_status'  => ['required_group' => 'super-admin'],
            ],
        ],
    ],

    /*-- DEPENDENCIES --------------------------------------------------------------*/
    'depends' => [],

    /*-- COMPATIBILITY -------------------------------------------------------------*/
    'compatibility' => [
        'php'       => '>=8.4',
        'laravel'   => '>=12.0',
        'webkernel' => '>=1.0.0',
    ],

    /*-- TIMESTAMP -----------------------------------------------------------------*/
    'created_at' => '2026-03-09T00:00:00+00:00',

    /*-- AUTHOR --------------------------------------------------------------------*/
    'author' => [
        'name'  => 'Acme Corp',
        'email' => 'security@acme.com',
        'url'   => 'https://acme.com',
    ],

    /*-- LICENSE -------------------------------------------------------------------*/
    'license' => 'proprietary',

    /*-- CERTIFICATION -------------------------------------------------------------*/
    // Managed by Webkernel. Do not edit manually.
    'certification' => [
        'certified_at'   => null,
        'certified_hash' => null,
    ],
];
```

### Key Rules

- `id` follows `<registry-id>::<vendor>/<slug>`. The registry hostname uses hyphens instead of dots (`webkernelphp.com` becomes `webkernelphp-com`).
- `version` must be semver.
- `active` can be toggled from the admin UI without editing the file.
- `certification` is managed by Webkernel's certification pipeline. Never set it manually.
- Slots you do not use must be omitted entirely.
- The `ffi` block must be absent if the module ships no native code.

---

## 4. Naming

| Segment    | Rules                                              | Example                           |
|------------|----------------------------------------------------|-----------------------------------|
| `registry` | Hostname of the registry                           | `webkernelphp.com`                |
| `vendor`   | Lowercase, hyphens only                            | `acme`                            |
| `slug`     | Lowercase, hyphens only                            | `payments`                        |
| `id`       | `<registry-id>::<vendor>/<slug>`                   | `webkernelphp-com::acme/payments` |

The registry enforces global uniqueness on `id`.

---

## 5. Party Values

| Value    | Who                  | Description                             |
|----------|----------------------|-----------------------------------------|
| `first`  | Numerimondes         | Core Webkernel modules                  |
| `second` | Your organisation    | Modules built for your own deployment   |
| `third`  | Community            | Publicly distributed modules            |

All three parties follow the same manifest format and the same rules.

---

## 6. Slots Reference

| Slot key                   | What it registers                                      |
|----------------------------|--------------------------------------------------------|
| `providers`                | Laravel service providers to boot                      |
| `helpers` / `helpers_paths`| PHP helper files to autoload                           |
| `route_groups`             | Route files grouped by middleware (`web`, `api`, etc.) |
| `route_paths`              | Additional standalone route files                      |
| `config_paths`             | Directories containing config files to merge           |
| `view_namespaces`          | Blade view namespaces                                  |
| `lang_paths`               | Translation namespaces                                 |
| `migration_paths`          | Directories containing migrations                      |
| `seeder_paths`             | Directories containing seeders                         |
| `command_paths`            | Directories scanned for Artisan commands               |
| `livewire_paths`           | Directories scanned for Livewire components            |
| `filament_paths`           | Directories scanned for Filament resources             |
| `asset_paths`              | Asset directories                                      |
| `blade_to_optimize_paths`  | Blade files to process with the Blaze optimizer        |
| `ffi`                      | Native module declarations (see Section 7)             |

---

## 7. FFI Integration

Only include the `ffi` block if the module ships compiled native code. Do not add it speculatively.

### Native Module Layout

```
modules/<registry>/<vendor>/<slug>/
+-- module.php
+-- native/
    +-- ffi/
    |   +-- module_payments.h   // C declarations for FFI::cdef()
    +-- lib/
    |   +-- module_payments.so
    |   +-- module_payments.so.sig
    +-- src/
        +-- module_payments.c
```

The ABI header (`webkernel_abi.h`) is global and lives in `bootstrap/webkernel/ffi/native/abi/`. Modules do not ship their own copy. They compile against the kernel's.

### Writing the Native Module

```c
#include "webkernel_abi.h"  /* from bootstrap/webkernel/ffi/native/abi/ */

int payments_process(const char *payload) { return 0; }
int payments_status()                     { return 1; }

int module_init()        { return 0; }
int module_shutdown()    { return 0; }
int module_healthcheck() { return 0; }  /* optional */

webkernel_function_entry module_functions[] = {
    {"payments_process", payments_process},
    {"payments_status",  payments_status},
    {NULL, NULL}
};

webkernel_module_info webkernel_module = {
    .name        = "webkernelphp-com::acme/payments",
    .version     = "1.0.0",
    .abi_version = WEBKERNEL_ABI_VERSION,   /* macro — never hardcode */
    .init        = module_init,
    .shutdown    = module_shutdown,
    .healthcheck = module_healthcheck,
};
```

### Build

```bash
gcc -shared -fPIC -O2 -Wall \
    -I$(WEBKERNEL_ROOT)/bootstrap/webkernel/ffi/native/abi \
    native/src/module_payments.c \
    -o native/lib/module_payments.so

strip --strip-debug native/lib/module_payments.so
```

Build must be reproducible. Document the exact toolchain version in `README.md`.

### Required Exports

| Symbol               | Type                        | Description                         |
|----------------------|-----------------------------|-------------------------------------|
| `webkernel_module`   | `webkernel_module_info`     | Module metadata struct              |
| `module_functions`   | `webkernel_function_entry[]`| Null-terminated function table      |
| `module_init`        | `int (*)(void)`             | Called on load; return 0 on success |
| `module_shutdown`    | `int (*)(void)`             | Called on unload; return 0          |
| `module_healthcheck` | `int (*)(void)`             | Optional; return 0 if healthy       |

Never hardcode the ABI version integer. Always use `WEBKERNEL_ABI_VERSION`.

### Signing

```bash
# Sign
openssl pkeyutl -sign \
    -inkey private.pem \
    -in <(sha256sum native/lib/module_payments.so | awk '{print $1}') \
    -out native/lib/module_payments.so.sig

# Verify before committing
openssl pkeyutl -verify \
    -pubin -inkey public.pem \
    -in <(sha256sum native/lib/module_payments.so | awk '{print $1}') \
    -sigfile native/lib/module_payments.so.sig
```

Submit your `public.pem` to the Webkernel key store during vendor registration. Keep `private.pem` offline.

### Access Control

`ffi.access` in `module.php` controls who can call what.

```php
'access' => [
    'scope'          => 'scoped',    // internal | scoped
    'required_group' => 'app-owner', // null | super-admin | app-owner
    'functions'      => [            // optional — overrides module default per function
        'payments_process' => ['required_group' => 'app-owner'],
        'payments_status'  => ['required_group' => 'super-admin'],
    ],
],
```

Resolution order: per-function override > module default. Both are controllable from the admin UI.

### Application Helpers

```php
if (ffi_can('webkernelphp-com::acme/payments', 'payments_process')) {
    $result = ffi_call('webkernelphp-com::acme/payments', 'payments_process', $payload);
}

ffi_assert('webkernelphp-com::acme/payments', 'payments_process');
```

```blade
@ffi_can('webkernelphp-com::acme/payments', 'payments_process')
    <button>Process Payment</button>
@end_ffi_can
```

### Constraints

- Do not call `fork`, `execve`, `ptrace`, or open raw sockets. These are blocked by the seccomp profile and will terminate the worker.
- Do not call `dlopen` from within a module.
- Do not link dynamically against libraries not in the SDK-approved list.
- Free all heap allocations in `module_shutdown()`.
- Validate all buffer sizes received as arguments before use.
- If the module is not thread-safe, document it in `README.md`.

---

## 8. Dependencies

```php
'depends' => [
    'webkernelphp-com::acme/core',
],
```

Webkernel resolves load order from `depends`. It refuses to unload a module that another active module depends on. Circular dependencies are rejected at load time.

---

## 9. Versioning

| Change type                        | Version bump |
|------------------------------------|--------------|
| Bug fix, no manifest or API change | PATCH        |
| New slot or new exported function  | MINOR        |
| Existing function signature changed| MAJOR        |
| Function or slot removed           | MAJOR        |
| ABI version bump                   | MAJOR        |

A MAJOR bump requires all dependent modules to be unloaded before the upgrade proceeds.

---

## 10. Submission Checklist

**Manifest**
- [ ] `id` follows `<registry-id>::<vendor>/<slug>`.
- [ ] `version` is valid semver.
- [ ] `compatibility` declares minimum `php`, `laravel`, and `webkernel` versions.
- [ ] `certification` is left as `null` / `null`.
- [ ] Unused slots are omitted.
- [ ] `CHANGELOG.md` and `README.md` present and up to date.

**FFI (only if `ffi` block is present)**
- [ ] `ffi.modules[].abi_version` uses `WEBKERNEL_ABI_VERSION` macro, not a hardcoded integer.
- [ ] `module_init`, `module_shutdown`, and `module_functions` exported.
- [ ] `module_healthcheck` exported if the module holds stateful resources.
- [ ] All heap allocations freed in `module_shutdown()`.
- [ ] No forbidden syscalls (`fork`, `exec`, `ptrace`, raw sockets, `dlopen`).
- [ ] Built with `-fPIC -shared` against `bootstrap/webkernel/ffi/native/abi/webkernel_abi.h`.
- [ ] Debug symbols stripped.
- [ ] Toolchain version documented in `README.md`.
- [ ] `.so` signed, `.so.sig` present alongside it.

---

## 11. Security Reporting

Vulnerability in Webkernel: `security@webkernelphp.com`. Do not open public issues.

Vulnerability in a third-party module: contact the module author via `author.email` in their `module.php`.
