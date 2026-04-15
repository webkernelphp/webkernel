# 07 — Editor Interface

**Author:** El Moumen Yassine — Numerimondes
**Platform:** WebKernel PHP

---

## Overview

The editor is a Filament-based interface. It is not a separate SPA. It is a set of Livewire
components rendered inside the WebKernel platform's Filament panel. This means it inherits the
platform's authentication, permission system, panel routing, and UI conventions without any
additional scaffolding.

---

## 7.1 Editor Layout

The editor is structured as a three-panel layout:

**Left panel — Block Library**
A searchable list of available block types. Blocks are grouped by category: Layout,
Content, Data, Media, Interactive. The user clicks a block to insert it at the current
selection, or drags it onto the canvas.

**Center panel — Canvas**
The live page preview. This is a Livewire component that renders the current page JSON
through the same rendering pipeline used for public output. An editing shell wraps the
output and adds:

- Selection overlays (clicking a block selects it and highlights it)
- Drag handles (reordering blocks by dragging)
- An insertion indicator (shows where a dropped block will land)

The canvas does not maintain its own DOM state. Every edit triggers a Livewire update that
re-renders the affected portion of the page. This is intentionally server-driven.

**Right panel — Block Properties**
When a block is selected, this panel shows its configuration tabs:

- **Content** — static text, image URL, or button label
- **Data** — field picker for variable binding (Collection, field)
- **Action** — on-click action configuration
- **Style** — spacing, color, typography (Phase 2)

When no block is selected, the panel shows page-level settings: title, slug, SEO metadata,
published status.

---

## 7.2 Technology Choices in the Editor

**Livewire** handles:
- Canvas re-rendering after every change
- Block property saving (debounced, auto-save on blur)
- Collection data preview in the canvas (real data, paginated)
- Page-level publishing action

**Alpine.js** handles:
- Block selection (click to select, click away to deselect)
- Panel tab switching
- Drag-and-drop reordering (using the Sortable.js plugin, already available in Filament)
- Visibility toggle states within the canvas

There is no custom JavaScript module for the editor. The editor behavior is expressed entirely
through Alpine.js directives in Blade templates and Livewire component methods.

---

## 7.3 The Block Registry

Every block type available in the editor is registered in a `BlockRegistry`. The registry maps
a block type identifier string to its block class, its Blade view, and its property form
definition.

```php
interface BlockRegistryContract
{
    public function register(string $type, BlockDefinition $definition): void;
    public function resolve(string $type): BlockDefinition;
    public function all(): Collection;
}
```

Third parties register custom block types through the service provider:

```php
BlockRegistry::register('my-custom-card', new BlockDefinition(
    label: 'Custom Card',
    icon: 'heroicon-o-rectangle-stack',
    class: MyCustomCardBlock::class,
    view: 'my-plugin::blocks.custom-card',
));
```

---

## 7.4 Context Switching

The editor always operates in the context of a site and an app. A context switcher in the
top bar allows the authenticated user to:

- Switch to a different app within the current site
- Switch to a different site (if they have access)

Switching context reloads the editor state, the page list, and the available Collections.

---

## 7.5 Collection Manager within the Editor

The Collection Manager is a Filament Resource embedded in the editor panel. It gives users:

- A list of all Collections in the current app
- A field builder (add, reorder, delete fields with a visual interface)
- A record browser (a Filament Table over the live Collection data)
- A publish action that triggers schema migration

The Collection Manager is not a separate page. It is a slide-over or a secondary panel within
the editor navigation, so users can define a Collection and immediately switch back to the
page editor to start binding blocks to it.

---

## 7.6 Preview Mode

The editor has a preview mode accessible from the top bar. In preview mode:

- The editing shell (selection overlays, drag handles) is removed
- The canvas renders exactly as the public output would
- A responsive breakpoint switcher allows viewing the page at mobile, tablet, and desktop widths

Preview mode does not leave the editor. It is a state toggle on the canvas Livewire component.
