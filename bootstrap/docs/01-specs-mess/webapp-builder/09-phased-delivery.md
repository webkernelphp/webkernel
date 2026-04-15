# 09 — Phased Delivery Plan

**Author:** El Moumen Yassine — Numerimondes
**Platform:** WebKernel PHP

---

## Phase 1 — The Core Loop

The goal of Phase 1 is a working end-to-end loop: a user creates data, binds it to a page,
and that page is served to visitors with live data — across as many sites and apps as needed.

Deliverables:

- Platform namespace scaffolding and core interface definitions
- Collection metadata layer (builder_collections, builder_collection_fields tables and models)
- Dynamic schema execution (Schema::create and Schema::table for field changes)
- Collection Manager UI in Filament (field builder, record browser, publish action)
- Block layout engine extension with DataBindable interface
- Variable mapping panel in the editor
- Repeater block with Livewire pagination and sorting
- Detail page context resolution
- Dynamic routing for list and detail pages (site-scoped)
- Basic on-click actions (Navigate To Page, Navigate To Detail, Navigate To URL)
- Form block writing to a Collection
- Multi-site and multi-app scoping throughout
- FieldTypeRegistry with Phase 1 field types (text, textarea, number, boolean, date, image)

---

## Phase 2 — API and Advanced Logic

Deliverables:

- API Connector with JSON schema introspection and field mapping
- Virtual Collections from external REST APIs (with configurable TTL caching)
- Conditional visibility rules on blocks (show/hide based on a field value or user state)
- Multi-step forms
- Filter and sort controls configurable by the user on Repeater blocks
- Action chaining (sequence of actions on a single event)
- Relation field type (foreign key to another Collection within the same app)
- Rich text field type with a Blade-safe renderer
- Custom 404 pages per site
- Basic role-based page access (public vs. authenticated)

---

## Phase 3 — Polish and Ecosystem

Deliverables:

- Reusable block components (user-saved block groups that can be inserted on any page)
- Page templates for common patterns (blog, directory, landing page, dashboard)
- Shared Collections across apps within the same site
- Export and import of page and collection definitions (JSON format)
- Marketplace integration for community-contributed block types
- Style system Phase 2 (spacing, color, typography controls in the editor)
- Version history for pages (ability to restore a previous version)
- Webhook actions (trigger an external URL on form submit or record create)

---

## Out of Scope (Permanent Constraints)

These are not planned for any phase and are deliberate exclusions:

- Custom PHP logic per user (sandboxed scripting) — this would require a sandboxing layer
  that is out of scope for the WebKernel platform's security model
- Native mobile output — the platform targets web only
- A bespoke JavaScript frontend framework — Alpine.js and Livewire are the permanent choice
