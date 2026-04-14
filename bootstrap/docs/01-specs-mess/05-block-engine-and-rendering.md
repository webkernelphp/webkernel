# 05 — Block Engine and Rendering Pipeline

**Author:** El Moumen Yassine — Numerimondes
**Platform:** WebKernel PHP

---

## Overview

The block system is the visual layer of the App Builder. It handles how pages are structured,
how content is placed, and how data from Collections flows into DOM elements. We build on top
of the existing block layout engine already present in the platform, extending it with data
binding capabilities.

---

## 5.1 The Existing Block Layout Foundation

The platform already has a block composition layer. It handles structural layout: rows, columns,
sections, and containers, as well as leaf elements such as headings, paragraphs, images, and
buttons. Pages are stored as a JSON tree of block definitions.

We do not replace this layer. We extend it.

The extension introduces a single new concept on every block: a **Data Source binding**. A
block that previously only accepted static content now also accepts a reference to a Collection
field or API field. All other layout and styling logic remains unchanged.

---

## 5.2 The DataBindable Interface

Every block in the system must implement `DataBindable`. Blocks that are purely structural or
static implement it through `StaticBlockTrait`, which returns null for all binding methods and
costs nothing at runtime.

```php
interface DataBindable
{
    public function getDataSource(): ?DataSourceContract;
    public function setDataSource(DataSourceContract $source): void;
    public function resolveBinding(array $context): mixed;
}

trait StaticBlockTrait
{
    public function getDataSource(): ?DataSourceContract { return null; }
    public function setDataSource(DataSourceContract $source): void {}
    public function resolveBinding(array $context): mixed { return null; }
}
```

Blocks that carry data implement `DataBindable` directly and use `HasDataBindingTrait` for the
resolution logic.

---

## 5.3 Variable Mapping

Variable Mapping is the mechanism that links a specific DOM element to a specific field in a
Collection or API source.

### In the editor

When the user selects a block (e.g., a heading), the right panel shows a "Content" tab with a
text input, and a "Data" tab with a field picker. The field picker shows:

```
Collection → Articles → Title
Collection → Articles → Published Date
API Source → GitHub Repos → Name
```

Choosing a field stores a binding descriptor on the block:

```json
{ "source": "collection", "collection_slug": "articles", "field": "title" }
```

### At render time

The rendering engine receives the current context (a row from a Repeater, or a single record
from a Detail page). It resolves the binding:

```php
interface VariableMappingContract
{
    public function bind(string $blockId, string $collectionSlug, string $field): void;
    public function resolve(string $blockId, array $context): mixed;
    public function toBladeExpression(string $blockId): string;
}
```

The Blade output for a bound heading becomes:

```blade
<h1>{{ $item->title ?? '' }}</h1>
```

Unbound headings output their static content directly. The renderer handles both cases
transparently.

---

## 5.4 The Repeater Block

The Repeater Block is a container block that iterates over a Collection and renders its child
blocks once per row. It is the primary mechanism for displaying lists.

### How it works

- The user places a Repeater block on a page and assigns a Collection to it.
- Inside the Repeater, the user places any other blocks (cards, headings, images, buttons).
- Each child block can bind its content to a field from the assigned Collection.
- At render time, the Repeater loops over the Collection rows and renders its children for
  each row, passing the row as the `$item` context.

### Livewire integration

The Repeater is a Livewire component. Pagination, sorting, and filtering are handled reactively
without page reloads. The user can configure:

- Number of items per page
- Default sort field and direction
- Whether to show a search input above the list

Alpine.js handles row-level local state (hover effects, expand/collapse, tooltip visibility).

```php
interface RepeaterBlockContract extends DataBindable
{
    public function getCollection(): CollectionContract;
    public function getPaginationConfig(): PaginationConfig;
    public function getSortConfig(): SortConfig;
    public function renderRow(array $item): View;
}
```

---

## 5.5 The Rendering Pipeline

The rendering pipeline converts a page definition (a JSON tree stored in the database) into
the final HTML delivered to the visitor.

### Steps

1. **Deserialize** — The page JSON is deserialized into a tree of `BlockContract` objects.
   Each block class is resolved from a `BlockRegistry` by its type identifier.

2. **Context resolution** — If the page is a Detail page, the platform loads the record
   matching the URL slug and injects it as the root context. If the page contains Repeater
   blocks, each Repeater mounts its own Livewire component with its Collection query.

3. **Render tree** — Each block renders itself as a Blade view. Container blocks recurse into
   their children, passing down the current context.

4. **Binding resolution** — Leaf blocks resolve their variable bindings against the context
   before rendering. Static blocks render their stored content directly.

5. **Alpine.js injection** — Action bindings and visibility rules are serialized as Alpine
   directives (`x-data`, `x-on:click`, `x-show`) and injected into the rendered HTML.

6. **Livewire wiring** — Repeaters and forms are Livewire components. They are embedded in the
   rendered HTML as `<livewire:builder.repeater ... />` and hydrated by Livewire on page load.

### The BlockRendererContract

```php
interface BlockRendererContract
{
    public function render(BlockContract $block, array $context): View;
    public function renderChildren(BlockContract $block, array $context): string;
}
```

### The single rendering truth principle

The editor preview and the public-facing output both use the same Blade rendering pipeline.
The editor wraps the pipeline output in an editing shell (selection overlays, drag handles,
property panels) but does not maintain its own parallel rendering logic. What the editor
shows is precisely what the visitor sees. This is not a nice-to-have — it is a hard
architectural requirement.
