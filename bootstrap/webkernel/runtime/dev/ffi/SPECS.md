# Webkernel FFI Module System

## 1. Objective

Design a secure, flexible, and maintainable FFI module system for Webkernel that:

- Leverages `ffi.enable=preload` for performance and stability.
- Allows dynamic loading and unloading of native modules without restarting PHP.
- Implements fine-grained access control per user and per function.
- Supports multi-tenant enterprise environments with proper isolation.
- Exposes governance controls to `app-owner` and delegated administrators via a graphical interface.
- Includes security, audit, and observability mechanisms.

**Platform target:** Linux x86_64 only (deliberate). PHP 8.4 minimum. Laravel 12 minimum.

---

## 2. Repository Layout

The FFI kernel lives entirely under `bootstrap/webkernel/ffi/`:

```
bootstrap/webkernel/ffi/
+-- native/
|   +-- abi/
|   |   +-- webkernel_abi.h     // single, global ABI — owned by the kernel
|   +-- ffi/
|   |   +-- webkernel.h         // PHP FFI::cdef() declarations for libwebkernel.so
|   +-- lib/
|   |   +-- libwebkernel.so     // native kernel library
|   +-- modules/                // loaded .so files land here at runtime
|   +-- registry/               // module registry state
+-- README.md
+-- SPECS.md
+-- SPECS_AUTHORING.md
```

`webkernel_abi.h` is the single source of truth for the ABI. It belongs to the kernel. Modules compile against it; they do not ship their own copy.

---

## 3. Server-Level FFI Configuration (Per Unix User)

PHP FFI is configured per Unix user via PHP-FPM pool configuration.

**Minimum PHP version: 8.4.**

```ini
; /etc/php/8.4/fpm/pool.d/webkernel.conf
[webkernel]
user = webkernel
group = webkernel
php_admin_value[ffi.enable] = preload
php_admin_value[ffi.preload] = /srv/webkernel/ffi/preload.php
```

| Server User | FFI Mode |
|-------------|----------|
| user1       | disabled |
| user2       | preload  |
| user3       | full     |

Multi-tenant isolation is enforced at the PHP-FPM pool level. Each tenant runs under a distinct Unix user with its own FFI configuration, cgroup limits, and seccomp profile.

---

## 4. Module Declaration in `module.php`

FFI is declared inside the standard Webkernel module manifest. The `ffi` block sits alongside all other module slots.

```php
<?php declare(strict_types=1);
return [
    /*-- IDENTITY ------------------------------------------------------------------*/
    'id'          => 'webkernelphp-com::acme/payments',
    'label'       => 'Payments',
    'description' => 'Payment processing via native FFI.',
    'version'     => '1.0.0',
    'active'      => true,

    /*-- NAMESPACE -----------------------------------------------------------------*/
    'namespace'   => 'WebModule\AcmePayments',

    /*-- REGISTRY ------------------------------------------------------------------*/
    'registry'    => 'webkernelphp.com',
    'vendor'      => 'acme',
    'slug'        => 'payments',

    /*-- PARTY ---------------------------------------------------------------------*/
    'party'       => 'second',

    /*-- PROVIDERS -----------------------------------------------------------------*/
    'providers'   => [
        WebModule\AcmePayments\Providers\PaymentsServiceProvider::class,
    ],

    /*-- FFI -----------------------------------------------------------------------*/
    'ffi' => [
        'enabled' => true,
        // Technical definition of native modules
        'modules' => [
            [
                'name'        => 'webkernelphp-com::acme/payments',
                'lib'         => 'native/lib/module_payments.so',
                'header'      => 'native/ffi/module_payments.h',
                'abi_version' => 1,
            ],
        ],
        // Access control: module-level default, optional per-function override
        'access' => [
            'scope'          => 'scoped',    // internal | scoped
            'required_group' => 'app-owner', // null | super-admin | app-owner
            'functions'      => [            // optional — overrides module-level default per function
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

    /*-- AUTHOR --------------------------------------------------------------------*/
    'author' => [
        'name'  => 'Acme Corp',
        'email' => 'security@acme.com',
        'url'   => 'https://acme.com',
    ],

    /*-- LICENSE -------------------------------------------------------------------*/
    'license' => 'proprietary',

    /*-- CERTIFICATION -------------------------------------------------------------*/
    'certification' => [
        'certified_at'   => null,
        'certified_hash' => null,
    ],
];
```

### FFI Block Reference

**`ffi.enabled`** — boolean. Controllable from the admin UI without editing the file.

**`ffi.modules`** — array of native module definitions.

| Key           | Required | Description                                              |
|---------------|----------|----------------------------------------------------------|
| `name`        | Yes      | Fully qualified module name, follows registry convention |
| `lib`         | Yes      | Path to `.so` file, relative to the module root          |
| `header`      | Yes      | Path to `.h` file used for `FFI::cdef()`                 |
| `abi_version` | Yes      | Must match `WEBKERNEL_ABI_VERSION` at load time          |

**`ffi.access`** — access control block.

| Key              | Required | Description                                                      |
|------------------|----------|------------------------------------------------------------------|
| `scope`          | Yes      | `internal` (all users) or `scoped` (privileged users only)       |
| `required_group` | Yes      | Module-level default: `null`, `super-admin`, or `app-owner`      |
| `functions`      | No       | Per-function overrides. Each entry overrides the module default  |

---

## 5. Access Control

### User Groups

| Group Name  | Description              | Access Level                                   |
|-------------|--------------------------|------------------------------------------------|
| app-owner   | Ultimate privileged user | Full access to scoped modules and internal FFI |
| super-admin | Delegated administrator  | Access to scoped modules per function grants   |
| user        | Standard user            | Internal Webkernel functions only              |

### Access Resolution Order

For any FFI call:

1. Check `ffi.enabled`. If `false`, deny.
2. Check `ffi.access.functions[function_name]` for a per-function override. If present, use its `required_group`.
3. Fall back to `ffi.access.required_group` (module-level default).
4. Verify the calling user belongs to the required group and the correct tenant context.

### Enforcement

```php
function callFFIFunction(string $module, string $function, User $user, array $args): mixed
{
    $meta = FFIRegistry::getMeta($module, $function);

    if (!$meta) {
        throw new FFIFunctionNotFoundException($function);
    }

    $requiredGroup = $meta['functions'][$function]['required_group']
        ?? $meta['required_group'];

    if ($requiredGroup && !$user->hasGroup($requiredGroup)) {
        throw new FFIAccessDeniedException($function, $user);
    }

    return $meta['ffi_obj']->$function(...$args);
}
```

### Application Helpers

```php
if (ffi_can('webkernelphp-com::acme/payments', 'payments_process')) {
    $result = ffi_call('webkernelphp-com::acme/payments', 'payments_process', $payload);
}

ffi_assert('webkernelphp-com::acme/payments', 'payments_process');
```

Blade directive for admin UI:

```blade
@ffi_can('webkernelphp-com::acme/payments', 'payments_process')
    <button>Process Payment</button>
@end_ffi_can
```

---

## 6. Dynamic Module Lifecycle

```
PHP
 |
FFI
 |
libwebkernel.so
 |
 +-- webkernel_load_module()
 +-- webkernel_unload_module()
 +-- webkernel_call()
 |
 +-- module_payments.so
 +-- module_cpu.so
 +-- module_ai.so
```

### State Machine

```
UNLOADED -> LOADING -> ACTIVE -> UNLOADING -> UNLOADED
                |
                v
             FAILED
```

On `init()` failure the module moves to `FAILED`. The kernel does not retry automatically. A `FAILED` module requires manual re-attempt by an `app-owner` via the admin UI or CLI. The failure is recorded in the audit log with error code and timestamp.

### Unload Safety

- `module_shutdown()` must return zero before `dlclose` is invoked.
- PHP-side FFI references to the module are invalidated immediately after unload.
- Any call to an invalidated reference throws `FFIModuleUnloadedException`.
- All heap memory allocated by the module must be freed within `module_shutdown()`.

### Dependency Resolution

Modules declare dependencies via `depends` in `module.php`. The kernel resolves load order automatically and refuses to unload a module that another active module depends on.

### Rollback

When a module is updated, the previous `.so` is retained as `.so.bak`. On `init()` failure of the new version, the kernel automatically reloads the backup and records the rollback event in the audit log.

---

## 7. ABI Specification

`bootstrap/webkernel/ffi/native/abi/webkernel_abi.h` — single, global, owned by the kernel:

```c
#ifndef WEBKERNEL_ABI_H
#define WEBKERNEL_ABI_H

#define WEBKERNEL_ABI_VERSION 1

typedef struct {
    const char *name;
    const char *version;
    int         abi_version;    /* must equal WEBKERNEL_ABI_VERSION */
    int       (*init)(void);
    int       (*shutdown)(void);
    int       (*healthcheck)(void); /* optional — return 0 if healthy */
} webkernel_module_info;

typedef struct {
    const char *name;
    void       *fn;
} webkernel_function_entry;

#endif
```

### Versioning Policy

- The ABI version is an integer incremented on any breaking change.
- Modules declaring an `abi_version` different from `WEBKERNEL_ABI_VERSION` are refused at load time with `WEBKERNEL_ERR_ABI_MISMATCH`.
- Non-breaking additions do not increment the ABI version.
- Breaking changes are documented in `CHANGELOG.md` with migration instructions.

---

## 8. Example Native Module

`modules/<registry>/<vendor>/<slug>/native/src/module_payments.c`:

```c
#include "webkernel_abi.h"  /* pulled from bootstrap/webkernel/ffi/native/abi/ at build time */

int payments_process(const char *payload) { return 0; }
int payments_status()                     { return 1; }

int module_init()        { return 0; }
int module_shutdown()    { return 0; }
int module_healthcheck() { return 0; }

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

Build against the kernel ABI:

```bash
gcc -shared -fPIC -O2 -Wall \
    -I$(WEBKERNEL_ROOT)/bootstrap/webkernel/ffi/native/abi \
    native/src/module_payments.c \
    -o native/lib/module_payments.so
```

---

## 9. Native Kernel API (`libwebkernel.so`)

```c
void* webkernel_load_module   (const char *path);
int   webkernel_unload_module (const char *name);
void* webkernel_get_function  (const char *module, const char *function);
```

Internally handles `dlopen`, `dlsym`, `dlclose`, module registry, dependency resolution, ABI version check, signature verification, rollback on failed `init()`, and audit event emission.

---

## 10. PHP Manager: `FfiModuleManager`

```php
final class FfiModuleManager
{
    private \FFI $ffi;

    public function __construct()
    {
        $this->ffi = \FFI::cdef(
            file_get_contents(base_path('bootstrap/webkernel/ffi/native/ffi/webkernel.h')),
            base_path('bootstrap/webkernel/ffi/native/lib/libwebkernel.so')
        );
    }

    public function load(string $path): void
    {
        $result = $this->ffi->webkernel_load_module($path);
        if ($result === null) {
            throw new FFIModuleLoadException("Failed to load module: {$path}");
        }
    }

    public function unload(string $module): void
    {
        $result = $this->ffi->webkernel_unload_module($module);
        if ($result !== 0) {
            throw new FFIModuleUnloadException("Failed to unload module: {$module}");
        }
    }
}
```

---

## 11. Security

### Sandboxing

All module workers run under a seccomp whitelist blocking: `fork`, `execve`, `ptrace`, `socket` with raw families, `clone` with `CLONE_NEWUSER`. Reference profile: `bootstrap/webkernel/runtime/seccomp/module_worker.json`.

Memory and CPU constrained per pool via cgroups v2:

```ini
; /etc/systemd/system/php8.4-fpm.service.d/cgroups.conf
[Service]
CPUQuota=200%
MemoryMax=512M
```

### Signature Verification

Every `.so` must be signed with an Ed25519 key. Verification is performed by `libwebkernel.so` on every `webkernel_load_module()` call. Unsigned modules are refused.

Compromised keys are added to `/etc/webkernel/revoked_keys/`. Modules signed exclusively by a revoked key are refused immediately with no grace period.

### Audit Log

| Field        | Description                                          |
|--------------|------------------------------------------------------|
| `timestamp`  | ISO 8601 UTC                                         |
| `event_type` | `load`, `unload`, `call`, `auth_failure`, `rollback` |
| `module`     | Fully qualified module name                          |
| `function`   | Function name (for `call` events)                    |
| `user`       | Authenticated user identifier                        |
| `tenant`     | Tenant identifier                                    |
| `result`     | `ok`, `error`, `denied`                              |
| `error_code` | Error code when result is not `ok`                   |

Retention defaults to 90 days, configurable per deployment.

Alerts triggered on:

- Three or more `auth_failure` events for the same user within 60 seconds.
- Any `load` of a module signed by a revoked key.
- Any module transition to `FAILED` state.

---

## 12. Observability

### Metrics (Prometheus format)

| Metric                                | Type      | Description                          |
|---------------------------------------|-----------|--------------------------------------|
| `webkernel_ffi_calls_total`           | Counter   | Total FFI calls, labelled by module  |
| `webkernel_ffi_call_errors_total`     | Counter   | Total FFI errors, labelled by module |
| `webkernel_ffi_call_duration_seconds` | Histogram | Call latency distribution            |
| `webkernel_module_state`              | Gauge     | 0=unloaded, 1=active, 2=failed       |
| `webkernel_module_memory_bytes`       | Gauge     | Estimated memory used by module      |

### Health Checks

Modules exporting `module_healthcheck()` are polled every 60 seconds (configurable). Results surfaced on `/healthz/ffi`. Modules not exporting `healthcheck` report as `active` without a functional check.

---

## 13. Multi-Tenant Isolation

Each tenant maps to a dedicated PHP-FPM pool under a distinct Unix user. Pools do not share memory, file descriptors, or FFI handles.

Access checks verify tenant ID in addition to group membership. A `super-admin` in tenant A cannot call scoped functions in tenant B's context.

| Resource     | Default    | Configurable |
|--------------|------------|--------------|
| CPU          | 200%       | Yes          |
| Memory       | 512 MB     | Yes          |
| Module slots | 16         | Yes          |
| Call rate    | 10 000/min | Yes          |

---

## 14. Runtime Support

Linux x86_64 only. This is a deliberate architectural decision.

| Runtime      | Support  |
|--------------|----------|
| PHP-FPM 8.4+ | Official |
| Swoole       | Official |
| RoadRunner   | Official |
| FrankenPHP   | Official |

---

## 15. Summary

| Concern            | Mechanism                                                   |
|--------------------|-------------------------------------------------------------|
| Module declaration | `ffi` block in `module.php`                                 |
| ABI                | Single global `webkernel_abi.h`, owned by the kernel        |
| Access control     | Per-function group check with tenant context verification   |
| UI governance      | `ffi_can()`, `ffi_assert()`, `@ffi_can` Blade directive     |
| Sandboxing         | seccomp + cgroups v2 per FPM pool                           |
| Module integrity   | Ed25519 signature verified on every load                    |
| Key revocation     | Revocation list, immediate rejection, no grace period       |
| Unload safety      | Mandatory `module_shutdown()` + PHP reference invalidation  |
| Failure handling   | `FAILED` state, manual re-attempt via UI or CLI             |
| Rollback           | Automatic on failed `init()`, `.so.bak` retained           |
| Tenant isolation   | Process-level via FPM pools, memory via cgroups             |
| Audit              | Append-only structured log, 90-day retention, alerting      |
| Observability      | Prometheus metrics, health check at `/healthz/ffi`          |
| Platform           | Linux x86_64, PHP 8.4+, Laravel 12+                        |
