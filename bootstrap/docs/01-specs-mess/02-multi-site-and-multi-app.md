# 02 — Multi-Site and Multi-App Architecture

**Author:** El Moumen Yassine — Numerimondes
**Platform:** WebKernel PHP

---

## Core Principle

A single WebKernel installation supports an **unlimited number of sites** and an **unlimited number
of apps** per site. There is no artificial cap at the platform level. The only limits are the
hardware resources of the host server.

This is a first-class architectural decision, not an afterthought. Every entity in the system —
Collections, Pages, Blocks, Routes, Forms — is scoped to a Site and to an App within that Site.

---

## Definitions

**Site** — A top-level domain or subdomain managed by the platform. Each site has its own
configuration, branding, set of apps, and (optionally) its own isolated database prefix or
schema. Example: `clientA.example.com`, `clientB.example.com`, or a custom domain.

**App** — A logical grouping of pages, collections, and routing rules within a site. A single
site can contain multiple apps. Example: a site for a school might contain a "Public Website"
app, an "Admissions Portal" app, and a "Staff Dashboard" app — all on the same domain,
resolved by path prefix or subdomain.

---

## Data Isolation Model

Each site is assigned a `site_id`. Each app is assigned an `app_id` scoped to its parent site.
All platform entities carry both foreign keys.

For Collections, we have two strategies depending on the installation:

**Strategy A — Prefixed tables (default):** Each Collection table is prefixed with the site
slug. Example: `clienta_articles`, `clienta_projects`. Simple, works on any single MySQL
database, easy to inspect and back up per site.

**Strategy B — Schema isolation (PostgreSQL):** Each site gets its own PostgreSQL schema.
Collections are created inside that schema. This provides stronger isolation and is recommended
for installations serving many tenants with strict data separation requirements.

The `CollectionContract` interface abstracts over both strategies. The rest of the platform does
not need to know which strategy is active.

```php
interface CollectionContract
{
    public function getSiteId(): int;
    public function getAppId(): int;
    public function getFields(): FieldCollection;
    public function getTableName(): string; // returns prefixed or schema-qualified name
    public function resolveQuery(): Builder;
}
```

---

## Routing Isolation

Dynamic routes generated for a Collection are always scoped to their site. The
`DynamicRouteRegistrar` resolves the current site at boot time (by hostname) and only registers
routes for that site's apps.

```php
interface DynamicRouteRegistrarContract
{
    public function register(CollectionContract $collection): void;
    public function getListRoute(CollectionContract $collection): string;
    public function getDetailRoute(CollectionContract $collection, mixed $identifier): string;
    public function getSiteScope(): SiteContract;
}
```

When a request comes in, the platform resolves the site, then resolves the app by path prefix,
then dispatches to the appropriate Livewire route.

---

## Editor Scope

In the Filament editor, the authenticated user always works within a selected site and a
selected app. The UI always shows the current site/app context in the top bar. Switching sites
or apps reloads the editor context without leaving the interface.

Collections, pages, and blocks created in one app are not visible or accessible from another
app, even within the same site. Sharing across apps (shared components, shared collections)
is a Phase 3 feature.

---

## Site Management

Site management is handled through the WebKernel system panel (the platform's own Filament
panel). From there, a platform administrator can:

- Create, enable, or disable sites
- Assign custom domains or subdomain rules
- Choose the data isolation strategy per site
- View resource usage per site

App management is handled within each site's own Filament panel. Site owners can:

- Create, rename, enable, or disable apps within their site
- Set the path prefix or subdomain for each app
- Choose which collections are visible to which app

---

## Namespace Placement

The multi-site and multi-app logic lives under:

```
Webkernel\System\   → site resolution, middleware, tenant boot
Webkernel\Panels\   → per-site panel configuration
Webkernel\Users\    → user-to-site and user-to-app assignments
```

The `SiteContract` and `AppContract` interfaces are defined under:

```
Webkernel\Connectors\Contracts\
```

since they are foundational contracts shared across all aptitude layers.
