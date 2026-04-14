# Cahier des Charges — WebKernel No-Code App Builder

**Project:** No-Code / Low-Code Web Application Builder
**Platform:** [WebKernel PHP](https://webkernelphp.com/)
**Stack:** Laravel, Filament, Livewire, Alpine.js, Blade
**Date:** April 2026
**Authors:** WebKernel Team

---

## 1. Executive Summary

We are building a self-hosted, no-code application builder on top of the WebKernel PHP platform. The goal is to allow application owners to design, build, and deploy full web applications — including data models, dynamic interfaces, navigation logic, and content — without writing a single line of code.

This is not a static site builder. This is a full application builder capable of handling real data, real business logic, and real users. We aim to go further than tools like Framer (which excels at design but fails at data and business logic), and we do so entirely within the PHP/Laravel ecosystem.

---

## 2. Objectives

- Allow non-technical users to create and manage their own database schemas (Collections) through a visual interface.
- Allow users to bind UI blocks to live data, from either internal collections or external APIs.
- Allow users to define navigation flows, list-detail patterns, and on-click actions without code.
- Deliver reactive, fast interfaces using Livewire and Alpine.js.
- Provide a self-hosted alternative to Webflow, Framer, and Budibase.
- Build on top of the existing block layout engine already present in the platform.

---

## 3. Scope

### 3.1 In Scope

- Visual page and layout editor (built on the existing block layout engine)
- Data Engine: dynamic collection creation and management
- API Connector: external data ingestion and schema mapping
- Variable Mapping: binding DOM elements to data fields
- Repeater Block: rendering lists from collections
- Dynamic Routing: auto-generated routes for detail pages
- On-Click Actions: navigation and event configuration
- Form Builder: forms that write to internal collections
- Private Dashboard support: authenticated views over collection data

### 3.2 Out of Scope (Phase 1)

- Custom PHP logic per user (sandboxed scripting)
- Multi-tenant SaaS deployment
- Native mobile output
- Version control / branching of pages

---

## 4. Technical Stack

| Layer | Technology |
|---|---|
| Backend framework | Laravel |
| Admin UI | Filament |
| Reactivity | Livewire |
| Frontend behavior | Alpine.js |
| Templating | Blade |
| Database | MySQL (dynamic schema via `Schema::create`) or PostgreSQL with JSONB columns |
| Architecture | Interface-heavy, trait-based, highly extensible |

We favor interfaces and traits throughout the codebase. Every major subsystem — blocks, data sources, renderers, actions — must be expressed through contracts (PHP interfaces) and composed through traits. This ensures extensibility without inheritance hell and makes each layer independently testable and replaceable.

---

## 5. Architecture Overview

### 5.1 The Block Layout Engine (Existing Foundation)

We build on top of the existing block layout system already present in the platform. This layer handles the structural composition of pages: columns, rows, sections, containers, and leaf-level elements such as headings, images, and buttons.

We extend this foundation by introducing the concept of a **Data Source** on every block component. A block that previously only accepted static content now also accepts a binding to a Collection field or an API field.

Every block must implement the `DataBindable` interface, which exposes:

```php
interface DataBindable
{
    public function getDataSource(): ?DataSourceContract;
    public function setDataSource(DataSourceContract $source): void;
    public function resolveBinding(array $context): mixed;
}
```

Blocks that do not need data binding implement a null version of this interface via a `StaticBlockTrait`.

---

### 5.2 The Data Engine

The Data Engine is the core of the application builder. It allows users to define their own data structures (Collections) and manage data records through a generated UI.

#### 5.2.1 Collections

A Collection represents a user-defined table. Each Collection has:

- A name and slug (used for routing)
- A set of Fields (text, number, date, boolean, relation, image, rich text)
- Optional validation rules per field
- Optional slug/identifier field for detail page routing

Collections are stored as actual database tables, generated on demand using `Schema::create`. Each field maps to a real column. The schema definition itself is persisted in a `collections` metadata table so we can reconstruct or migrate it at any time.

The `Collection` entity implements:

```php
interface CollectionContract
{
    public function getFields(): FieldCollection;
    public function getTableName(): string;
    public function resolveQuery(): Builder;
}
```

Field types implement:

```php
interface FieldContract
{
    public function getColumnDefinition(): Fluent;
    public function getCastType(): string;
    public function getValidationRules(): array;
}
```

#### 5.2.2 API Connector

Users can connect to external REST APIs and treat the response as a virtual Collection. The connector:

1. Accepts a URL, method, optional headers, and optional authentication.
2. Fetches the response and presents its schema to the user.
3. The user maps JSON fields (e.g., `response.data[].title`) to named virtual fields.
4. The result is a `VirtualCollection` that implements `CollectionContract` and behaves identically to a local Collection from the binding layer's perspective.

```php
interface ApiConnectorContract
{
    public function fetchSchema(): array;
    public function mapField(string $jsonPath, string $alias): void;
    public function resolveAsCollection(): VirtualCollection;
}
```

---

### 5.3 Variable Mapping

Variable Mapping is the layer that links DOM elements to data fields. It is the equivalent of what Framer and Webflow call "data binding."

#### How it works

- In the page editor, when a user selects a block element (e.g., a heading or an image), a side panel offers a "Data Source" option.
- The user navigates: Collection -> Articles -> Title.
- The system stores this as a binding descriptor on the block: `{ collection: 'articles', field: 'title' }`.
- At render time, the Blade/Livewire rendering engine resolves this binding against the current context (a row from a Repeater, or a single record from a Detail page) and outputs `{{ $item->title }}`.

The mapping layer must implement:

```php
interface VariableMappingContract
{
    public function bind(string $blockId, string $collection, string $field): void;
    public function resolve(string $blockId, array $context): mixed;
    public function toBladeExpression(string $blockId): string;
}
```

Traits provide default resolution logic. Custom block types can override resolution through the interface without touching the trait.

---

### 5.4 The Repeater Block

The Repeater Block is a container block that iterates over a Collection and renders its child blocks once per row.

- The user assigns a Collection to the Repeater.
- The Repeater exposes a `$item` context variable to all child blocks.
- Child blocks with variable bindings resolve their values against `$item`.
- Livewire handles pagination, filtering, and sorting reactively without page reloads.
- Alpine.js handles local UI state (hover effects, open/close states) within each repeated row.

The Repeater implements:

```php
interface RepeaterBlockContract extends DataBindable
{
    public function getCollection(): CollectionContract;
    public function getPaginationConfig(): PaginationConfig;
    public function renderRow(array $item): View;
}
```

---

### 5.5 Dynamic Routing

When a user creates a Collection named "Projects," the system automatically registers the following routes without any code:

- `/projects` — list view (uses a Repeater-based page template)
- `/projects/{slug}` — detail view (uses a Detail page template)

Route registration is handled by a `DynamicRouteRegistrar` that reads all published Collections at boot time and registers Livewire-backed routes for each one.

```php
interface DynamicRouteRegistrarContract
{
    public function register(CollectionContract $collection): void;
    public function getListRoute(CollectionContract $collection): string;
    public function getDetailRoute(CollectionContract $collection, mixed $identifier): string;
}
```

The detail page automatically loads the record matching the slug and makes it available as the page context. All blocks on that page resolve their bindings against this single record.

---

### 5.6 On-Click Actions

Every interactive element (button, card, row in a Repeater) can have one or more actions attached to it through the editor.

Available actions in Phase 1:

- **Navigate To Page** — go to a static page defined in the builder.
- **Navigate To Detail** — go to `/collection/{id}` for the current row item.
- **Navigate To URL** — external link.
- **Submit Form** — triggers a Livewire form submission.
- **Toggle Visibility** — show/hide another block (Alpine.js-driven).

Actions implement:

```php
interface ActionContract
{
    public function getType(): string;
    public function getConfig(): array;
    public function toAlpineDirective(): string;
    public function toLivewireCall(): ?string;
}
```

The editor serializes actions as JSON on the block. The renderer injects the correct Alpine or Livewire directive at render time.

---

### 5.7 Form Builder

Users can create forms that write data to a Collection:

- Each form field maps to a Collection field.
- Validation rules come from the field definition.
- On submit, a Livewire component handles the write and returns feedback.
- The user can configure a redirect action on success.

The Form Block implements `DataBindable` and `ActionContract`. A `FormRendererTrait` provides the default Livewire wiring, which block types can override.

---

### 5.8 Rendering Pipeline

The rendering pipeline takes a page definition (a JSON tree of blocks with their configurations and bindings) and produces the final HTML served to the end user.

Steps:

1. **Deserialize** the page JSON into a tree of block objects.
2. **Resolve data sources**: any Repeater or Detail page loads its data via Livewire at mount.
3. **Render tree**: each block renders itself as a Blade view. Nested blocks recurse.
4. **Inject Alpine directives**: action and visibility bindings are injected as `x-data`, `x-on:click`, etc.
5. **Livewire wires up reactivity**: pagination, form submission, and filter interactions happen over Livewire without full page reloads.

The pipeline uses a `BlockRendererContract`:

```php
interface BlockRendererContract
{
    public function render(BlockContract $block, array $context): View;
    public function renderChildren(BlockContract $block, array $context): string;
}
```

---

## 6. Editor Interface

The editor is built inside Filament. It provides:

- A canvas area (the page preview) rendered live via Livewire.
- A left panel for block library (drag and drop or click to insert).
- A right panel for block properties (static content, data bindings, actions, styles).
- A top bar for page settings, preview mode, and publishing.

The editor does not rely on a custom JavaScript framework. Alpine.js handles all drag-drop state, panel toggles, and selection highlights. Livewire handles saving, page switching, and data preview updates.

We deliberately avoid building a JS-heavy SPA editor. The editor is a Livewire component. The canvas is re-rendered server-side when the user makes changes. This keeps the editor consistent with the renderer and eliminates a category of bugs caused by editor/runtime divergence.

---

## 7. Competitive Positioning

| Capability | Framer | Webflow | Budibase | WebKernel Builder |
|---|---|---|---|---|
| Visual design quality | Excellent | Very Good | Basic | Good (Phase 1) |
| Data / Collections | Limited | Good | Good | Full |
| External API data | Very limited | Limited | Good | Full |
| Forms writing to DB | Not supported | Limited | Supported | Supported |
| Private dashboards | Not supported | Limited | Supported | Supported |
| Self-hosted | No | No | Yes | Yes |
| PHP / Laravel native | No | No | No | Yes |
| No JS required from user | Yes | Yes | Yes | Yes |

Our primary differentiation against Framer is the data layer. Framer is a design tool that has added some data features. We are a data-first application builder that happens to also handle layout and design. Users who need real applications — with forms, authenticated views, dashboards, and live data — will find us more capable, at no hosting cost overhead, because the stack runs on any standard PHP server.

---

## 8. Phased Delivery Plan

### Phase 1 — Foundation

- Block layout engine extension with `DataBindable` interface
- Collection manager (create, edit, delete fields, browse records)
- Variable mapping panel in editor
- Repeater block with Livewire pagination
- Dynamic routing for list and detail pages
- Basic on-click navigation actions
- Static form with Collection write

### Phase 2 — API and Advanced Logic

- API Connector with schema mapping
- Virtual Collections from external APIs
- Conditional visibility rules on blocks
- Multi-step forms
- Filter and sort controls on Repeater blocks

### Phase 3 — Polish and Ecosystem

- Reusable block templates (user-saved components)
- Page templates for common patterns (blog, directory, dashboard)
- Role-based access to pages (authenticated routes)
- Export / import of page and collection definitions
- Marketplace for community blocks

---

## 9. Development Principles

- **Interfaces first.** Every subsystem defines a contract before any implementation. No concrete class is referenced directly where an interface can be used.
- **Traits for shared behavior.** Cross-cutting behavior (rendering, validation, data resolution) is delivered through traits that classes opt into. No base class proliferation.
- **Livewire for reactivity.** All server-driven reactivity (data loading, form handling, pagination) goes through Livewire. We do not introduce a JS state management layer.
- **Alpine.js for local UI state.** Anything that does not require a server round-trip (toggling panels, hover states, accordion behavior) is handled by Alpine.js in-template.
- **Blade as the single rendering truth.** The editor preview and the public-facing output use the same Blade rendering pipeline. What the editor shows is what the visitor sees.
- **No bespoke JS framework.** We do not build or depend on a custom JavaScript frontend framework. The DOM manipulation we need is covered by Alpine.js. This is a deliberate constraint.

---

## 10. Glossary

| Term | Definition |
|---|---|
| Collection | A user-defined data model stored as a database table |
| Virtual Collection | A read-only collection whose data comes from an external API |
| Block | A UI element in the page editor (heading, image, button, repeater, etc.) |
| Repeater | A block that renders its children once per row of a Collection |
| Variable Mapping | The binding between a block element and a Collection field |
| Detail Page | An auto-generated page that displays a single record from a Collection |
| Dynamic Route | A URL pattern auto-registered by the system based on a Collection |
| Data Source | The Collection or API assigned to a block as its data provider |
| Action | A configured event handler attached to an interactive block element |

---

*End of document.*
