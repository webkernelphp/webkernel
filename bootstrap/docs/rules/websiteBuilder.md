# Webkernel Website Builder — Specification

Maintained by Numerimondes — https://webkernelphp.com/
Founder and Architect: El Moumen Yassine

---

## Architecture Overview

```
Storage Layer
  (filesystem | database)
        |
        v
Content Resolution Layer
  (locale detection, translation routing)
        |
        v
Block / Section Registry
  (first-party | module | third-party blocks)
        |
        v
Render Pipeline
  (static fragments | dynamic fragments)
        |
        v
Public Output
  (hybrid static/dynamic pages)
```

---

## Scope

This document specifies the architecture, storage contracts, multilingual strategy, rendering pipeline, CMS capabilities, and database collection builder for the Webkernel website builder.

The website builder is a first-party Webkernel module that provides:

- A drag-and-drop page composition interface inside the Filament panel
- A block and section registry supporting first-party, module-provided, and third-party blocks
- A multilingual content storage system with hybrid filesystem and database persistence
- A public-facing render pipeline that produces hybrid static and dynamic output
- A CMS for structured content types
- A visual database collection creator and management interface

The builder produces pages that work correctly in two rendering contexts: inside the Filament admin panel (preview mode) and in the public-facing output. The rendered HTML must be identical in both contexts. There are no preview-only or admin-only rendering branches inside block components.

---

## Block Structure

Each block is a self-contained directory. Specific filesystem paths are defined per deployment context. The canonical structure is:

```
[block-name]/
├── partials/
│   ├── Default/
│   │   ├── Default[BlockName].php
│   │   └── view.blade.php
│   ├── Simple/
│   │   └── view.blade.php
│   └── [Variant]/
│       ├── [Variant][BlockName].php
│       └── view.blade.php
├── LICENCE
├── [BlockName]Block.php
├── thumbnail.png
└── view.blade.php
```

Each block has its own namespace. Namespace resolution handles collisions between first-party, module-provided, and third-party blocks. Resolution priority is: first-party overrides module, module overrides third-party, unless explicitly configured otherwise.

### Block Definition Class

Each block defines its identity, schema, and available variants through a block definition class.

```php
namespace Webkernel\Builder\Blocks;

use Webkernel\Builder\Contracts\BuilderBlock;

class HeroBlock extends BuilderBlock
{
    public function name(): string
    {
        return 'hero';
    }

    public function label(): string
    {
        return __('webkernel::builder.blocks.hero');
    }

    public function schema(): array
    {
        return [
            TranslatableText::make('heading'),
            TranslatableText::make('subheading'),
            TranslatableText::make('cta_label'),
            UrlField::make('cta_href'),
            MediaField::make('background_image'),
        ];
    }

    public function variants(): array
    {
        return ['Default', 'Simple', 'Slider'];
    }

    public function defaultVariant(): string
    {
        return 'Default';
    }
}
```

---

## Multilingual Storage

### Detection Strategy

Translatable fields are detected dynamically by checking for the presence of a `translations` key in the stored value. A field value is considered translatable if and only if it matches this structure:

```json
{
    "translations": {
        "en": { "value": "Hello world" },
        "fr": { "value": "Bonjour le monde" },
        "ar": { "value": "مرحبا بالعالم" }
    }
}
```

Fields that do not contain a `translations` key are treated as locale-agnostic scalar values. The detection is non-destructive: existing scalar values remain scalar until explicitly migrated to the translatable structure.

### Storage Backends

The website builder supports two storage backends. An application may use one or both simultaneously depending on its performance and operational requirements.

**Database storage (default)**

Translatable fields are stored in a JSON column. The detection and resolution layer handles reading and writing through the standard Eloquent model interface.

**Filesystem storage**

For applications that use the website builder without requiring a database for page content (flat-file or hybrid deployments), content may be stored in the filesystem using `storage_path()`. Filesystem storage enables pre-rendering of static fragments, which is the mechanism that makes public pages fast.

```php
// Configuration in webkernel.php
'builder' => [
    'storage' => 'filesystem', // 'database' | 'filesystem' | 'hybrid'
    'filesystem_path' => storage_path('webkernel/pages'),
],
```

Filesystem-stored content is organized as follows (paths relative to the configured `filesystem_path`):

```
[filesystem_path]/
├── pages/
│   ├── [page-slug]/
│   │   ├── meta.json          -- page metadata, settings, block list
│   │   ├── blocks/
│   │   │   ├── [block-id].json  -- block content with translations structure
│   │   │   └── ...
│   │   └── static/
│   │       ├── en.html          -- pre-rendered static fragment (English)
│   │       ├── fr.html          -- pre-rendered static fragment (French)
│   │       └── ar.html          -- pre-rendered static fragment (Arabic)
│   └── ...
└── media/
    └── ...
```

### Translation Resolution

At render time, the resolution layer receives the active locale and traverses the block content, replacing each translatable field with the value for that locale. Fallback order is: requested locale, then the application's fallback locale, then the first available locale in the `translations` map.

```php
namespace Webkernel\Builder\Translation;

class TranslationResolver
{
    public function resolve(mixed $value, string $locale): mixed
    {
        if (! is_array($value) || ! array_key_exists('translations', $value)) {
            return $value;
        }

        $translations = $value['translations'];

        return $translations[$locale]
            ?? $translations[config('app.fallback_locale')]
            ?? reset($translations)
            ?? null;
    }
}
```

### Translatable Field Contract

Any field in a block schema that should be multilingual implements the `Translatable` interface. The builder UI presents a locale switcher for these fields. Non-translatable fields (URLs, media references, numeric settings) are stored as plain scalars.

---

## Hybrid Static and Dynamic Rendering

The website builder produces pages that combine static pre-rendered fragments with dynamic server-side output. This is the mechanism that makes public-facing pages fast without requiring a separate static site generator.

### The Two Fragment Types

**Static fragments** are blocks whose content does not change based on the current request: hero sections, feature grids, testimonials, footer content. These are pre-rendered to HTML files per locale during a build step triggered by content saves. The render pipeline reads the HTML file directly and injects it into the page response without executing PHP for that fragment.

**Dynamic fragments** are blocks whose output depends on request context: search results, user-specific content, database-driven collections, forms. These are rendered normally by Blade at request time.

A page may contain any mixture of static and dynamic blocks. The render pipeline handles the assembly transparently.

### Static Fragment Generation

Static fragments are generated when:

- A page is saved in the admin panel
- A block's content is updated
- The locale configuration changes
- A manual rebuild is triggered

```php
namespace Webkernel\Builder\Renderer;

class StaticFragmentWriter
{
    public function write(string $pageSlug, string $blockId, string $locale, string $html): void
    {
        $path = storage_path(
            "webkernel/pages/{$pageSlug}/static/{$locale}/{$blockId}.html"
        );

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $html);
    }

    public function read(string $pageSlug, string $blockId, string $locale): string|null
    {
        $path = storage_path(
            "webkernel/pages/{$pageSlug}/static/{$locale}/{$blockId}.html"
        );

        return File::exists($path) ? File::get($path) : null;
    }
}
```

### Page Render Pipeline

```php
namespace Webkernel\Builder\Renderer;

class PageRenderer
{
    public function render(Page $page, string $locale): string
    {
        $fragments = [];

        foreach ($page->blocks as $block) {
            if ($block->isStatic()) {
                $html = $this->staticWriter->read($page->slug, $block->id, $locale);

                if ($html !== null) {
                    $fragments[] = $html;
                    continue;
                }
            }

            // Dynamic block or cache miss: render at request time
            $fragments[] = $this->renderBlock($block, $locale);
        }

        return implode('', $fragments);
    }
}
```

This approach is a genuine alternative to static site generation. Pages load with the speed of static files for content blocks while retaining full server-side dynamism for interactive or personalized sections. No separate build toolchain is required. No CDN revalidation complexity. The filesystem is the cache.

---

## CMS — Structured Content Types

The website builder includes a CMS for managing structured content that is reused across pages or rendered independently.

### Content Type Definition

Content types are defined in PHP and registered with the builder. Each content type declares its fields, its storage model, and its available display templates.

```php
namespace App\ContentTypes;

use Webkernel\Builder\Cms\ContentType;

class BlogPost extends ContentType
{
    public function name(): string { return 'blog_post'; }

    public function fields(): array
    {
        return [
            TranslatableText::make('title'),
            TranslatableRichText::make('body'),
            MediaField::make('cover_image'),
            DateField::make('published_at'),
            SelectField::make('status')->options(['draft', 'published', 'archived']),
        ];
    }
}
```

### CMS Admin Interface

The CMS admin interface is built on Filament resources. Each registered content type gets a Filament resource generated automatically (or defined explicitly for customization). The interface follows all Webkernel component conventions: `wcs-*` classes, RTL support, dark mode, and accessibility requirements from the HTML Component Specification.

---

## Database Collection Builder

The database collection builder allows administrators to define database schemas, create collections, and build management interfaces through a drag-and-drop interface in the admin panel — without writing migrations manually.

### Collection Definition

A collection is a named, schema-defined data structure. Administrators define field types, validation rules, relationships, and display settings through the UI. The builder translates these definitions into Laravel migrations and Filament resources.

```
Collection: Products
Fields:
  - name          (translatable text, required)
  - slug          (text, unique, auto-generated from name)
  - description   (translatable rich text)
  - price         (decimal, min: 0)
  - stock         (integer, default: 0)
  - category      (belongs-to: Category)
  - images        (has-many media)
  - published     (boolean, default: false)
```

### Data Sources

The collection management interface supports connecting to multiple data sources:

- The application's primary database (default)
- External databases via configured connection strings
- REST API endpoints (read-only collections)
- Filesystem-based JSON collections (for static deployments)

Data source connections are defined in `config/webkernel.php` under the `collections.sources` key. The drag-and-drop field builder respects the constraints of each source type (e.g., REST API sources do not expose migration controls).

### Generated Interface

For each collection, the builder generates:

- A Filament resource with list, create, edit, and view pages
- A Filament table with sortable, filterable, and searchable columns based on the field definitions
- A Filament form using Webkernel components for all field types
- API endpoints for use by the website builder's dynamic blocks (optional, enabled per collection)

The generated interface uses all Webkernel component conventions and satisfies the full Definition of Done checklist from the HTML Component Specification.

---

## Admin Panel Integration

The website builder integrates into the Filament panel as a set of pages and resources registered through the Webkernel plugin class.

```php
namespace Webkernel;

use Filament\Contracts\Plugin;
use Filament\Panel;

class WebkernelPlugin implements Plugin
{
    public function getId(): string
    {
        return 'webkernel';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->pages([
                \Webkernel\Builder\Pages\PageBuilder::class,
                \Webkernel\Builder\Pages\PageList::class,
            ])
            ->resources([
                \Webkernel\Builder\Resources\ContentTypeResource::class,
                \Webkernel\Builder\Resources\CollectionResource::class,
                \Webkernel\Builder\Resources\MediaLibraryResource::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        // CSS injection for the builder interface
        FilamentView::registerRenderHook(
            PanelsRenderHook::STYLES_AFTER,
            fn () => view('webkernel::styles.site-builder'),
        );
    }
}
```

---

## Security Constraints

All security rules from the HTML Component Specification apply to the website builder without exception. Additional constraints specific to the builder:

- Block schema fields that accept user-generated HTML (rich text) must pass through the configured sanitizer before storage and before rendering. Raw HTML from the database is never passed directly to `{!! !!}`.
- Block definitions from third-party packages are loaded through a registry that validates namespace and class signatures before instantiation. Arbitrary class names from the database are never instantiated directly.
- API endpoints generated for collections require authentication by default. Public access must be explicitly opted into per collection.
- Filesystem paths used for static fragment storage are derived from slugs that are validated against an alphanumeric-and-hyphen allowlist. No user input reaches `File::get()` or `File::put()` without this validation.

---

## Performance Constraints

All performance constraints from the HTML Component Specification apply. Additional constraints specific to the builder:

- Static fragment reads must not trigger database queries. The filesystem read is the entire cost.
- The page renderer must resolve and assemble all static fragments before issuing any database queries for dynamic blocks.
- Block schema resolution (determining which fields are translatable) is cached per request. It must not rerun the detection loop on every field access.
- The drag-and-drop editor interface loads block thumbnails lazily. Thumbnails are not preloaded for blocks that are not in the current viewport.
- Collection query interfaces use Filament's built-in pagination. No collection view may load an unbounded result set.

---

## Definition of Done

A website builder block is considered complete only when every item in this checklist is satisfied.

- Block definition class registered with correct name, label, schema, and variants
- All translatable fields use the `translations` key structure
- Static fragment generation implemented and tested for all supported locales
- Dynamic fallback rendering implemented and tested
- Block renders correctly in the admin panel preview
- Block renders identically in the public-facing output
- RTL layout verified with `dir="rtl"` on `<html>`
- Dark mode verified
- All user-generated content sanitized before storage and before rendering
- Security constraints verified: no direct `{!! !!}` of unsanitized content, no dynamic class instantiation from database values
- Filament resource (if applicable) satisfies the HTML Component Specification Definition of Done
- Static fragment files invalidated and regenerated on content save

---

Numerimondes — Casablanca, Morocco
https://webkernelphp.com/
