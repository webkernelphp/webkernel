# 04 — The Data Engine

**Author:** El Moumen Yassine — Numerimondes
**Platform:** WebKernel PHP

---

## Overview

The Data Engine is the core of the application builder. It gives users the ability to define
their own data structures (Collections), manage their records, and connect external APIs as
virtual data sources — all without writing SQL or PHP.

Every entity in the Data Engine is scoped to a site and an app. See `02-multi-site-and-multi-app.md`
for isolation details.

---

## 4.1 Collections

A Collection is a user-defined data model. Internally, it maps to a real database table created
on demand. From the user's perspective, it is simply a named list of typed fields.

### What a Collection has

- A name (human label) and a slug (used for routing and table naming)
- A set of Fields, each with a type, optional validation, and optional display label
- An optional identifier field used as the slug for detail page URLs
- A `site_id` and `app_id` scoping it to the correct context
- A status (draft / published)

### Field Types (Phase 1)

- `text` — short string
- `textarea` — long string / rich text
- `number` — integer or decimal
- `boolean` — true/false toggle
- `date` — date or datetime
- `image` — file reference (stored path or URL)
- `relation` — foreign key to another Collection within the same app

### How Collections are stored

The schema definition is persisted in a `builder_collections` metadata table (site-scoped).
Each field is a row in `builder_collection_fields`. When the user publishes a Collection,
the platform executes `Schema::create()` to generate the actual table in the database.

When a field is added or removed after publication, the platform runs `Schema::table()` to
alter the existing table. Column renames generate a copy-and-drop sequence to preserve data.

The `CollectionContract` interface:

```php
interface CollectionContract
{
    public function getSiteId(): int;
    public function getAppId(): int;
    public function getSlug(): string;
    public function getFields(): FieldCollection;
    public function getTableName(): string;
    public function resolveQuery(): Builder;
    public function isPublished(): bool;
}
```

The `FieldContract` interface:

```php
interface FieldContract
{
    public function getName(): string;
    public function getType(): string;
    public function getColumnDefinition(): Fluent;
    public function getCastType(): string;
    public function getValidationRules(): array;
    public function isRequired(): bool;
}
```

Field types are registered in a `FieldTypeRegistry`. Third parties can add custom field types
by implementing `FieldContract` and registering them through the service provider.

### The Collection Manager UI

Within the Filament editor, the Collection Manager is a dedicated Filament Resource. It provides:

- A list of all Collections in the current app
- A field editor (add, reorder, rename, delete fields)
- A data browser (view and edit records in any published Collection)
- A publish/unpublish toggle that triggers schema migration

---

## 4.2 API Connector

Users can connect to external REST APIs and treat the response as a virtual Collection. The
result behaves identically to a local Collection from the binding layer's perspective.

### Workflow

1. The user provides a URL, HTTP method, optional headers, and optional authentication
   (Bearer token, API key header, or Basic Auth).
2. The platform fetches the endpoint and introspects the JSON response to infer a schema.
3. The user sees the inferred fields and maps each one to a named alias
   (e.g., `response.data[].title` becomes the `title` field).
4. The user gives the Virtual Collection a name and saves it.
5. From the editor, the Virtual Collection appears in the same field picker as local Collections.

### VirtualCollection

A `VirtualCollection` implements `CollectionContract` but does not map to a database table.
Its `resolveQuery()` method instead issues an HTTP request, parses the response, and returns a
paginator-compatible collection of mapped objects.

```php
interface ApiConnectorContract
{
    public function fetchSchema(): array;
    public function mapField(string $jsonPath, string $alias): void;
    public function resolveAsCollection(): VirtualCollection;
}
```

The `Webkernel\Integration\Api\Rest` namespace in the existing connector layer provides the
HTTP transport. The App Builder wraps it through `ApiConnectorContract`.

### Caching

Virtual Collection results are cached per request by default. An optional TTL can be configured
per Virtual Collection. Cache invalidation is manual (a "Refresh" button in the editor) or
time-based.

---

## 4.3 The Data Engine and Multi-App Scope

A Collection belongs to exactly one app. It is not visible or queryable from another app within
the same site unless explicitly shared (Phase 3 feature).

The `builder_collections` metadata table has the following key columns:

```
id, site_id, app_id, name, slug, table_name, status, identifier_field, created_at, updated_at
```

The `builder_collection_fields` table:

```
id, collection_id, name, label, type, options (JSON), validation_rules (JSON), sort_order, is_required
```

These two tables are the authoritative source of truth for the entire Data Engine. Everything
the platform does — rendering, routing, form validation — derives from these records.
