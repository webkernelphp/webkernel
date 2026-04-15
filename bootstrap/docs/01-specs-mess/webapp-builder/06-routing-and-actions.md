# 06 — Dynamic Routing and On-Click Actions

**Author:** El Moumen Yassine — Numerimondes
**Platform:** WebKernel PHP

---

## 6.1 Dynamic Routing

When a user publishes a Collection, the system automatically registers routes for it. No PHP
file is touched. No artisan command is run manually. The routes appear the moment the
Collection is published.

### Generated routes

For a Collection named "Projects" with the slug `projects`, the system registers:

- `GET /projects` — the list page (uses a Repeater-based page template)
- `GET /projects/{slug}` — the detail page (loads the matching record)

Routes are scoped per site. The `DynamicRouteRegistrar` resolves the current site at boot time
and registers only the routes for that site's published Collections.

```php
interface DynamicRouteRegistrarContract
{
    public function registerAll(SiteContract $site): void;
    public function register(CollectionContract $collection): void;
    public function getListRoute(CollectionContract $collection): string;
    public function getDetailRoute(CollectionContract $collection, mixed $identifier): string;
}
```

### Route registration strategy

Routes are registered in the platform's boot sequence via a cached route file. When a
Collection is published or unpublished, the route cache is invalidated and rebuilt. On
high-traffic installations, this rebuild is queued to avoid blocking the request that triggered
the publish action.

### Detail page context

On a detail route, the platform resolves the record by matching the `{slug}` parameter against
the Collection's designated identifier field. The record is injected into the Livewire page
component as the root context. All blocks on the page resolve their bindings against this
single record.

If no record matches the slug, the platform returns a 404. The user can optionally configure
a custom 404 page per site.

---

## 6.2 Form Builder Routing

Forms that write to a Collection do not need a separate route. They are embedded blocks within
a page. The Livewire form component handles the POST internally, validates against the
Collection's field rules, writes the record, and triggers the configured success action.

---

## 6.3 On-Click Actions

Every interactive block element — buttons, cards, Repeater rows — can have one or more actions
attached to it through the editor. Actions are configured in the right panel under the "Action"
tab and stored as JSON on the block.

### Available actions (Phase 1)

- **Navigate To Page** — go to a static page within the same app, selected by name.
- **Navigate To Detail** — go to the detail route for the current `$item` in a Repeater context.
  The system generates the correct URL automatically using the item's identifier field.
- **Navigate To URL** — go to an arbitrary external URL.
- **Submit Form** — trigger the Livewire form submission for a Form block on the same page.
- **Toggle Visibility** — show or hide another named block on the page using Alpine.js.

### Action interface

```php
interface ActionContract
{
    public function getType(): string;
    public function getConfig(): array;
    public function toAlpineDirective(): string;
    public function toLivewireCall(): ?string;
}
```

Navigation actions resolve to `x-on:click="window.location.href = '...'"` or
`wire:click="navigateTo(...)"` depending on whether a Livewire transition is needed.

Toggle actions resolve to `x-on:click="$dispatch('toggle-block', { id: 'block-xyz' })"` with
a corresponding `x-on:toggle-block.window="..."` on the target block.

### Action chaining (Phase 2)

In Phase 2, actions can be chained. Example: on form submit, validate, then write to
Collection, then navigate to a success page, then send a notification email. Each step in the
chain is an `ActionContract` instance processed in sequence.

---

## 6.4 App-Level Navigation Configuration

Each app has a navigation configuration resource in the editor. The user can:

- Define a primary navigation menu (links to pages within the app)
- Set a homepage (the page rendered at the app root route)
- Set a 404 page
- Configure authenticated vs. public access per page

This is stored in a `builder_app_navigation` table and rendered by a Blade partial injected
into all public-facing page layouts of the app.
