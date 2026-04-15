# 03 — Technical Stack and Namespace Map

**Author:** El Moumen Yassine — Numerimondes
**Platform:** WebKernel PHP

---

## Stack Decisions

| Layer | Technology | Rationale |
|---|---|---|
| Backend framework | Laravel | Industry standard, rich ecosystem, excellent DB tooling |
| Admin / Editor UI | Filament | The existing platform UI layer; panels, tables, forms |
| Reactivity | Livewire | Server-side reactivity without a JS framework |
| Frontend behavior | Alpine.js | Already bundled with Filament; DOM state without writing JS |
| Templating | Blade | Single rendering truth for editor and public output |
| Database | MySQL or PostgreSQL | MySQL default; PostgreSQL for schema isolation |
| Architecture | Interface-first, trait-composed | Extensibility without inheritance complexity |

We do not introduce any additional JavaScript framework. Alpine.js covers all local DOM state.
Livewire covers all server-driven reactivity. This is a deliberate, permanent constraint.

---

## Architecture Principles

Every major subsystem is expressed as a PHP interface first. No concrete class is referenced
where an interface can be used. Shared behavior is delivered through traits. This produces a
codebase where:

- Each layer can be replaced without touching the rest
- Each layer can be tested in isolation
- Third parties can implement new block types, field types, or action types without forking core
- There is no deep inheritance chain anywhere in the system

---

## Existing Connector Layer

The platform already has a rich connector layer under:

```
bootstrap/webkernel/aptitudes/connectors/
```

This layer provides integration contracts for communication channels (email, SMS, WhatsApp,
chat), payment gateways, social platforms, API protocols (REST, GraphQL, SOAP), Git providers,
and productivity tools (MCP, PDF, Office). It is also the home of the `contracts/` and
`traits/` directories shared across all aptitudes.

The App Builder will consume this connector layer, particularly:

- `Webkernel\Integration\Api\` — for the API Connector (external data sources)
- `Webkernel\Connectors\Contracts\` — for foundational contracts (SiteContract, AppContract)
- `Webkernel\Connectors\Traits\` — for shared trait behavior

The connector layer is **read-only from the App Builder's perspective**. The App Builder does
not modify the connector layer; it depends on its contracts.

---

## Namespace Map

```
/* Core Layer: Connectors */
Webkernel\Connectors\Contracts\   → aptitudes/connectors/contracts
Webkernel\Connectors\Traits\      → aptitudes/connectors/traits
Webkernel\Connectors\             → aptitudes/connectors/facades

/* Domain Connectors */
Webkernel\Communication\          → aptitudes/connectors/src/communication
Webkernel\Social\                 → aptitudes/connectors/src/social
Webkernel\Payment\                → aptitudes/connectors/src/payment
Webkernel\Integration\            → aptitudes/connectors/src/integration
Webkernel\Productivity\           → aptitudes/connectors/src/productivity
Webkernel\FFI\                    → aptitudes/connectors/src/native/ffi

/* Aptitudes Layer: Core Business Logic */
Webkernel\Pages\                  → aptitudes/pages
Webkernel\Panels\                 → aptitudes/panels
Webkernel\Plugins\                → aptitudes/plugins
Webkernel\Users\                  → aptitudes/users
Webkernel\Widgets\                → aptitudes/widgets

/* Backend Layer: System Infrastructure */
Webkernel\System\                 → backend/src
Webkernel\Exceptions\             → backend/exceptions
Webkernel\Providers\              → backend/providers

/* Support Layer: Application Data Models */
App\Models\                       → support/app-models

/* Platform Layer: Interface and Widgets */
Webkernel\Arcanes\                → platform/arcanes
Webkernel\Panel\                  → platform/panel
Webkernel\Platform\SystemPanel\   → platform/system_panel

/* Fallback */
Webkernel\                        → src
```

---

## Where the App Builder Lives

The App Builder is a new aptitude. Its namespace and directory will be:

```
Webkernel\Builder\                → aptitudes/builder/src
Webkernel\Builder\Contracts\      → aptitudes/builder/contracts
Webkernel\Builder\Traits\         → aptitudes/builder/traits
```

Its Filament resources, Livewire components, and Blade views live under:

```
aptitudes/builder/resources/
aptitudes/builder/livewire/
aptitudes/builder/views/
```

The App Builder registers itself as a Filament plugin via a service provider under
`Webkernel\Providers\`, following the same pattern as all other platform aptitudes.
