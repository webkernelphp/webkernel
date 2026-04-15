# 08 — Where to Start: The Build Order Decision

**Author:** El Moumen Yassine — Numerimondes
**Platform:** WebKernel PHP

---

## The Question

> Should we start with the database creation (the Data Engine), or with something else?

This is the right question to ask first. The answer is: **yes, start with the Data Engine —
but only the metadata layer, not the dynamic schema migration.**

Here is the full reasoning and the recommended build order.

---

## Why the Data Engine Comes First

Everything in this system depends on Collections. The block binding system needs Collections
to exist. The rendering pipeline needs Collections to query. The routing system needs
Collections to generate URLs. The editor needs Collections to show in the field picker.

If we start with the editor or the block system, we will be building against phantom data
structures, mocking everything, and constantly revisiting decisions as the real data model
crystallizes. Starting with the data layer means every subsequent layer builds on a stable,
real foundation.

However, within the Data Engine itself, there is an important distinction:

- The **metadata layer** (the `builder_collections` and `builder_collection_fields` tables,
  the `CollectionContract`, the `FieldContract`, the `FieldTypeRegistry`) can and should be
  built first. This is pure Laravel — models, migrations, interfaces. No dynamic schema magic.

- The **dynamic schema execution** (`Schema::create` on user-defined tables) should come second,
  once the metadata layer is solid and tested. This is the riskier part and benefits from
  having a complete field type definition to work from.

---

## Recommended Build Order

### Step 1 — Platform Scaffolding (1–2 days)

Register the `Webkernel\Builder\` namespace and directory. Create the service provider.
Register it in the platform. Define the core foundational interfaces:

- `SiteContract` and `AppContract` (these may already exist partially in the platform)
- `CollectionContract`
- `FieldContract`
- `BlockContract`
- `DataBindable`

Write no implementations yet. Only interfaces. This step forces us to think through the
contracts before writing any code that depends on them.

### Step 2 — Collection Metadata Layer (2–3 days)

Create the migrations for `builder_collections` and `builder_collection_fields`. Create the
Eloquent models for both. Implement `CollectionContract` and `FieldContract` on these models.

Build the `FieldTypeRegistry` with the Phase 1 field types: text, textarea, number, boolean,
date, image. Each field type is a class implementing `FieldContract`.

Write unit tests for the registry and for each field type's column definition and validation
rules. This step produces working, testable code with no UI.

### Step 3 — Dynamic Schema Execution (2–3 days)

Build the `SchemaBuilder` service that reads a published `Collection` model and calls
`Schema::create()` to generate the actual database table. Handle the alter-table case for
adding or removing fields after publication.

Write integration tests using an in-memory SQLite database. Test that publishing a Collection
with three fields produces a table with the correct columns, types, and nullable settings.
Test that adding a field to a published Collection runs the correct `Schema::table()` call.

### Step 4 — Collection Manager UI (2–3 days)

Build the Filament Resource for Collections. Give it a field builder that uses Filament's own
repeater component (fields are a nested list inside the collection form). Add the publish
action that triggers the schema execution.

At this point we have a working no-code database creation tool. A user can open the editor,
create a Collection named "Articles" with fields for title, body, and publication date, click
"Publish," and immediately have a real `articles` table in the database, browsable through a
generated Filament table.

**This is the first meaningful milestone. Ship it. Use it. Make sure it feels right before
proceeding.**

### Step 5 — Block System Extension (3–4 days)

Extend the existing block layout engine with `DataBindable`. Add the `StaticBlockTrait` for
blocks that do not need binding. Build the `VariableMappingContract` and its default
implementation. Build the field picker UI for the editor's right panel.

At this point, blocks can be bound to Collection fields. Nothing is rendered with live data
yet — the binding is stored but the renderer does not yet act on it.

### Step 6 — Rendering Pipeline with Data (3–4 days)

Build the rendering pipeline layer that resolves variable bindings at render time. Build the
Repeater block as a Livewire component. Connect the Repeater to a Collection query.

At this point, a user can place a Repeater on a page, bind its children to Collection fields,
and see real data rendered in the editor canvas and in the public output.

**This is the second major milestone. The core loop — create data, display data — is complete.**

### Step 7 — Dynamic Routing (1–2 days)

Build the `DynamicRouteRegistrar`. Wire it to the boot sequence. Generate list and detail
routes for all published Collections. Build the detail page context resolution.

### Step 8 — On-Click Actions (2 days)

Build the `ActionContract` implementations for Phase 1 actions. Wire them to the editor's
action tab. Inject Alpine directives at render time.

### Step 9 — Form Builder (2–3 days)

Build the Form block as a Livewire component. Wire field definitions to Collection field
validation rules. Handle the record write and the success action.

### Step 10 — Multi-Site and Multi-App Wiring (2–3 days)

Add `site_id` and `app_id` scoping to all Collection queries. Build the context switcher
in the editor. Wire the `DynamicRouteRegistrar` to site-scoped boot.

---

## What Not to Start With

- **Do not start with the editor canvas.** It depends on the block system, which depends on
  the data layer. Starting here means building against mocks that will change.

- **Do not start with the routing system.** Routes are generated from published Collections.
  Without Collections, there is nothing to route to.

- **Do not start with the API Connector.** It is a Virtual Collection — a convenience layer
  over a real Collection. The real Collection layer must exist first.

- **Do not start with styling or design polish.** The functional loop (data in, data displayed)
  must work end-to-end before spending time on visual refinement.

---

## Summary

```
Step 1  →  Platform scaffolding and core interfaces
Step 2  →  Collection metadata layer (models, migrations, field types)
Step 3  →  Dynamic schema execution (Schema::create / Schema::table)
Step 4  →  Collection Manager UI in Filament          ← Milestone 1
Step 5  →  Block system extension with DataBindable
Step 6  →  Rendering pipeline + Repeater block        ← Milestone 2
Step 7  →  Dynamic routing
Step 8  →  On-click actions
Step 9  →  Form builder
Step 10 →  Multi-site / multi-app scoping
```

The answer to the original question is: **start with the database, specifically the metadata
tables. Not with dynamic schema creation. Not with the UI. With the contracts and the models
that define what a Collection is.** Everything else follows from that foundation.
