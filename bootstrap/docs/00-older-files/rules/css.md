# Webkernel CSS Design System — Specification

Maintained by Numerimondes — https://webkernelphp.com/
Founder and Architect: El Moumen Yassine

---

## Architecture Overview

```
Design Tokens (wds-)
        |
        v
Semantic Layer (--wcs-*)
        |
        v
Components (wcs-)
        |
        v
Hooks (whk-)
        |
        v
Overrides (90_overrides)
```

---

## Philosophy

We do not trust Tailwind CSS as a default authoring tool. We write pure CSS wherever possible. Tailwind utility classes are only used via `@apply` to compose Filament-compatible styles when pulling Filament's visual language into our own class surface is the cleanest option. We never write `class="flex items-center gap-2"` in markup when a properly named CSS class can carry that intent.

We build on Filament v5. We do not fight Filament. We do not duplicate what it already provides. We extend it.

Our CSS lives in `.css.blade.php` files. This is intentional. Blade gives us `@if`, `@unless`, conditional rendering, PHP variable interpolation, and access to config at render time. CSS alone cannot replace that control layer. These files are injected into Filament panels via `FilamentView::registerRenderHook()` and into the website builder via its own equivalent hooks.

We had inconsistent naming before — prefixes like `site-bg`, `webkernel-overlay-menu`, `webkernel-section` scattered across inline `<style>` blocks without structure or coherence. That era is over.

---

## Delivery Mechanism

### Filament Panel Injection

CSS blade files are injected into panels via service providers or Filament plugin boot methods using `FilamentView::registerRenderHook()` against `PanelsRenderHook` constants.

The standard hook for stylesheet injection is `PanelsRenderHook::STYLES_AFTER`, which places content immediately after Filament's own stylesheets in the document `<head>`. This guarantees our overrides take effect without specificity fights.

```php
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;

FilamentView::registerRenderHook(
    PanelsRenderHook::STYLES_AFTER,
    fn () => view('webkernel::styles.panel'),
);
```

Where `resources/views/webkernel/styles/panel.blade.php` assembles the style blocks:

```blade
@include('webkernel::styles.tokens')
@include('webkernel::styles.components')
@include('webkernel::styles.hooks')
```

Each included partial is a `.css.blade.php` file wrapped in a `<style>` block.

### Contextual Injection via Other Hooks

| Hook                             | Use case                                              |
|----------------------------------|-------------------------------------------------------|
| `STYLES_AFTER`                   | Global tokens, base reset, component library          |
| `BODY_START`                     | Layout shell variables that depend on PHP config      |
| `SIDEBAR_START`                  | Sidebar-specific overrides                            |
| `TOPBAR_BEFORE`                  | Topbar height token injection                         |
| `PAGE_START`                     | Per-page conditional styles                           |

Example of a PHP-aware token injection at body start:

```blade
{{-- resources/views/webkernel/styles/layout-tokens.blade.php --}}
<style>
    :root {
        --wds-topbar-height: {{ $showTopbar ? '2rem' : '0px' }};
        --wds-content-offset: {{ $showTopbar
            ? 'var(--wds-content-offset-with-topbar)'
            : 'var(--wds-content-offset-without-topbar)' }};
    }
</style>
```

### Webkernel Render Hooks

The website builder and other Webkernel surfaces expose their own render hooks via `Webkernel\View\RenderHooks`. CSS can be injected into those surfaces using the same mechanism:

```php
FilamentView::registerRenderHook(
    \Webkernel\View\RenderHooks::AUTH_BG_LIGHT,
    fn () => asset('images/auth-bg-light.jpg'),
);
```

New hooks are added to `Webkernel\View\RenderHooks` as the system evolves. They are documented there as they are introduced.

---

## What We Inherit From Filament

Filament v5 ships its own full color palette as CSS custom properties on `:root`. We do not redefine them. We use them directly.

```css
/* These are already available from Filament v5 — we do not touch them */
--primary-50  through  --primary-950
--gray-50     through  --gray-950
--danger-50   through  --danger-950
--success-50  through  --success-950
--warning-50  through  --warning-950
--info-50     through  --info-950
```

Our token layer (`wds-`) sits above this palette. It references Filament's variables via `var()`. It never redeclares raw oklch values unless Filament does not provide the token we need.

Dark mode follows Filament's `.dark` class strategy on `<html>`. We never use `@media (prefers-color-scheme: dark)` for anything that lives inside the Filament or website builder context.

Filament's `fi-*` hook classes remain available and are not replaced. Our `whk-` layer is parallel to them, not a replacement. When we need to override a Filament element, we target `fi-*` classes exclusively in `90_overrides.css.blade.php`.

---

## Prefix Taxonomy

Every CSS class we write starts with one of these prefixes. No exceptions.

| Prefix   | Full name                    | Scope                                                              |
|----------|------------------------------|--------------------------------------------------------------------|
| `wds-`   | Webkernel Design System      | Design tokens as CSS custom properties. Never used as a utility class in HTML — only consumed via `var()` inside other rules. |
| `wcs-`   | Webkernel Components         | Structural and visual component classes.                          |
| `whk-`   | Webkernel Hooks              | Customization hooks, parallel to Filament's `fi-*` system. Empty selectors by default. |
| `wut-`   | Webkernel Utilities          | Single-purpose utility classes. One rule per class.               |
| `wsb-`   | Webkernel Site Builder       | Components that belong to the website builder.                    |

We also use the long-form `webkernel-` prefix for Blade-level identifiers that need to be unambiguous in templates — layout wrappers, JS hook targets, semantic labels. These are always accompanied by a `wcs-` or `whk-` class that carries the actual styles. The `webkernel-` name is a semantic label, not a style vehicle.

---

## Naming Rules

1. Always start with the prefix. No exceptions.
2. BEM suffixes after the prefix: `wcs-btn`, `wcs-btn--lg`, `wcs-btn__icn`.
3. Abbreviations must be consistent. Use only the table below.
4. No mixing of layers: `wds-` is tokens only, `wcs-` is components only, `whk-` is hooks only.
5. `whk-` classes carry no default styles. They are the public API for customization.
6. Never put `!important` outside `90_overrides.css.blade.php`.
7. Never hardcode color values in component files. Reference a `--wcs-*` or Filament token via `var()`.

### Standard Abbreviations

| Abbreviation | Meaning     |
|--------------|-------------|
| `btn`        | button      |
| `ctn`        | container   |
| `wrp`        | wrapper     |
| `col`        | column      |
| `nav`        | navigation  |
| `hdr`        | header      |
| `ftr`        | footer      |
| `sdb`        | sidebar     |
| `mdl`        | modal       |
| `inp`        | input       |
| `lbl`        | label       |
| `icn`        | icon        |
| `sec`        | section     |
| `tbl`        | table       |
| `pg`         | page        |
| `blk`        | block       |
| `ovl`        | overlay     |
| `bg`         | background  |

---

## Specificity Policy

Uncontrolled specificity is a maintenance hazard. These rules are enforced across all component and utility files.

- Never use element selectors alone (e.g., `button {}`, `input {}`). Always qualify with a class.
- Maximum specificity allowed in component files: `0-2-0` (two class selectors, no IDs, no elements).
- No descendant selectors deeper than two levels inside a component's own scope.
- No ID selectors anywhere except `90_overrides.css.blade.php`, and only when targeting a third-party element that cannot be reached otherwise.
- `!important` is allowed only in `90_overrides.css.blade.php`. Every use requires a comment explaining why specificity cannot solve the problem.
- Overrides of Filament's own styles go exclusively in `90_overrides.css.blade.php`.

---

## Isolation Strategy

All selectors must begin with a Webkernel prefix (`wds-`, `wcs-`, `whk-`, `wut-`, `wsb-`, or `webkernel-`). No global bare-element resets outside `02_base.css.blade.php`. No styling of raw HTML elements unless the selector is scoped inside `.wcs-prose`. If Webkernel CSS is embedded in a host application, the prefix strategy guarantees zero leakage in both directions.

---

## Responsive Strategy

Mobile-first architecture. Styles for the smallest viewport are written first. Larger breakpoints are additive overrides.

Breakpoints are defined as CSS custom properties and must be used consistently:

```css
:root {
    --wds-bp-sm: 640px;
    --wds-bp-md: 768px;
    --wds-bp-lg: 1024px;
    --wds-bp-xl: 1280px;
}
```

Media queries use these tokens via `env()` or are written against the literal values defined here. No hardcoded pixel values inside component files that differ from this scale. If a breakpoint is needed that does not appear in this table, it must be added to the token layer first and documented with a rationale.

```css
/* Correct */
@media (min-width: 768px) {
    .wcs-card { flex-direction: row; }
}

/* Wrong — arbitrary value not in the token scale */
@media (min-width: 820px) {
    .wcs-card { flex-direction: row; }
}
```

---

## Z-Index Scale

All z-index values come from this scale. No arbitrary values are allowed anywhere in the codebase.

```css
:root {
    --wds-z-base:      1;
    --wds-z-dropdown:  100;
    --wds-z-sticky:    200;
    --wds-z-overlay:   400;
    --wds-z-modal:     1000;
    --wds-z-toast:     1100;
}
```

Components use these tokens:

```css
.wcs-mdl     { z-index: var(--wds-z-modal);    }
.wcs-toast   { z-index: var(--wds-z-toast);    }
.wcs-dropdown { z-index: var(--wds-z-dropdown); }
```

If a new z-index tier is genuinely needed, it must be added to the token scale with a name and rationale. It may not be hardcoded inline.

---

## File Structure

```
resources/
├── css/webkernel/
│   ├── 01_tokens.css.blade.php           -- wds- custom properties only
│   ├── 02_base.css.blade.php             -- resets, typography base, motion, scrollbars
│   ├── 03_layout.css.blade.php           -- container, shell, grid, full-bleed escape
│   ├── 04_components.css.blade.php       -- wcs- component classes
│   ├── 05_hooks.css.blade.php            -- whk- hook selectors (empty by default)
│   ├── 06_utilities.css.blade.php        -- wut- single-purpose utilities
│   ├── 07_site-builder.css.blade.php     -- wsb- website builder components
│   └── 90_overrides.css.blade.php        -- fi-* and third-party overrides, last resort
│
└── views/webkernel/styles/
    ├── panel.blade.php                   -- assembled <style> block for panel injection
    ├── site-builder.blade.php            -- assembled <style> block for website builder
    └── layout-tokens.blade.php           -- PHP-aware token overrides injected at BODY_START
```

Import order in the assembled partials is strict and must not be changed.

---

## Layer 1 — Design Tokens (`wds-`)

All tokens are CSS custom properties on `:root`. They are never output as classes in HTML markup. They are consumed inside `wcs-`, `whk-`, and `wsb-` rules via `var()`.

```css
:root {
    /* Spacing */
    --wds-space-outer:  0.65rem;
    --wds-space-inner:  0.65rem;
    --wds-space-top:    0.65rem;
    --wds-space-bottom: 0.65rem;
    --wds-bottom-clearance: 3.6rem;

    /* Component heights */
    --wds-topbar-height:         2rem;
    --wds-sidebar-toggle-height: 2.5rem;

    /* Border-radius */
    --wds-radius-sm:        4px;
    --wds-radius-md:        7px;
    --wds-radius-lg:        13px;
    --wds-radius-xl:        20px;
    --wds-radius-container: 7px;
    --wds-radius-content:   13px;

    /* Effects */
    --wds-backdrop-blur: 10px;

    /* Shadows (light) */
    --wds-shadow-y:              2px;
    --wds-shadow-blur:           4px;
    --wds-shadow-spread:         0px;
    --wds-shadow-opacity:        0.06;
    --wds-shadow-border-opacity: 0.08;

    /* Shadows (dark) */
    --wds-shadow-dark-opacity:        0.3;
    --wds-shadow-dark-border-opacity: 0.08;

    /* Scrollbar */
    --wds-scrollbar-size:          3.5px;
    --wds-scrollbar-opacity:       0.3;
    --wds-scrollbar-opacity-hover: 0.5;

    /* Breakpoints — read-only reference values */
    --wds-bp-sm: 640px;
    --wds-bp-md: 768px;
    --wds-bp-lg: 1024px;
    --wds-bp-xl: 1280px;

    /* Z-index scale */
    --wds-z-base:      1;
    --wds-z-dropdown:  100;
    --wds-z-sticky:    200;
    --wds-z-overlay:   400;
    --wds-z-modal:     1000;
    --wds-z-toast:     1100;

    /* Semantic color aliases (light) */
    --wds-color-bg:      var(--gray-50);
    --wds-color-surface: oklch(96.8% 0.007 247.896);
    --wds-color-text:    var(--gray-950);
    --wds-color-border:  rgba(0, 0, 0, 0.08);

    /* Sidebar */
    --wds-sidebar-padding-x:         1rem;
    --wds-sidebar-item-margin-left:  1rem;
    --wds-sidebar-item-margin-right: 0.8rem;

    /* Reading mode */
    --wds-word-spacing:   normal;
    --wds-letter-spacing: normal;

    /* Computed layout offsets */
    --wds-content-offset-with-topbar: calc(
        var(--wds-topbar-height)
        + (var(--wds-space-top) * 3)
        + var(--wds-space-bottom)
        + var(--wds-bottom-clearance)
    );
    --wds-content-offset-without-topbar: calc(
        (var(--wds-space-top) * 2)
        + var(--wds-space-bottom)
        + var(--wds-bottom-clearance)
    );
    --wds-content-offset-with-toggle: calc(
        var(--wds-sidebar-toggle-height)
        + var(--wds-space-top)
        + var(--wds-space-bottom)
        + var(--wds-bottom-clearance)
    );

    --wds-content-offset: var(--wds-content-offset-with-topbar);
    --wds-sidebar-height: calc(100vh - var(--wds-content-offset));
}

.dark {
    --wds-color-bg:      var(--gray-950);
    --wds-color-surface: var(--gray-950);
    --wds-color-text:    #ffffff;
    --wds-color-border:  rgba(255, 255, 255, 0.08);
}

[data-wk-reading-mode="relaxed"] {
    --wds-word-spacing:   0.16em;
    --wds-letter-spacing: 0.12em;
}
```

### Semantic Component Color Properties (`--wcs-*`)

A small set of semantic color properties bridges the raw token layer and the component layer. Components reference these, not Filament variables directly.

```css
:root {
    --wcs-bg:          var(--wds-color-bg);
    --wcs-surface:     var(--wds-color-surface);
    --wcs-text:        var(--wds-color-text);
    --wcs-border:      var(--wds-color-border);
    --wcs-accent:      var(--primary-600);
    --wcs-accent-soft: var(--primary-100);
}

.dark {
    --wcs-bg:          var(--gray-950);
    --wcs-surface:     var(--gray-900);
    --wcs-text:        var(--gray-50);
    --wcs-border:      rgba(255, 255, 255, 0.08);
    --wcs-accent:      var(--primary-400);
    --wcs-accent-soft: var(--primary-950);
}
```

---

## Layer 2 — Components (`wcs-`)

We write pure CSS. We use `@apply` only when composing on top of a Filament or Tailwind pattern is genuinely cleaner than rewriting it.

Every component exposes a `whk-` hook alongside it. All transitions are gated behind `prefers-reduced-motion: no-preference`.

### Button

```css
.wcs-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    border-radius: var(--wds-radius-md);
    border: 1px solid transparent;
    cursor: pointer;
}

@media (prefers-reduced-motion: no-preference) {
    .wcs-btn {
        transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }
}

.wcs-btn--sm  { padding: 0.25rem 0.75rem; font-size: 0.875rem; }
.wcs-btn--md  { padding: 0.5rem 1rem;     font-size: 0.875rem; }
.wcs-btn--lg  { padding: 0.75rem 1.5rem;  font-size: 1rem; }

.wcs-btn--primary {
    background-color: var(--primary-600);
    color: #ffffff;
}
.wcs-btn--primary:hover  { background-color: var(--primary-500); }
.wcs-btn--primary:active { background-color: var(--primary-700); }

.wcs-btn--ghost {
    background-color: transparent;
    border-color: var(--wcs-border);
    color: var(--wcs-text);
}
.wcs-btn--ghost:hover       { background-color: var(--gray-100); }
.dark .wcs-btn--ghost:hover { background-color: var(--gray-800); }

.wcs-btn--loading { cursor: wait; }
.wcs-btn--disabled,
.wcs-btn[aria-disabled="true"] {
    opacity: 0.4;
    pointer-events: none;
}
```

### Card

```css
.wcs-card {
    background-color: var(--wcs-surface);
    border: 1px solid var(--wcs-border);
    border-radius: var(--wds-radius-content);
    overflow: hidden;
    box-shadow:
        0 var(--wds-shadow-y) var(--wds-shadow-blur) var(--wds-shadow-spread)
        rgba(0, 0, 0, var(--wds-shadow-opacity));
}

.wcs-card__hdr {
    padding-block: var(--wds-space-inner);
    padding-inline: var(--wds-space-outer);
    border-block-end: 1px solid var(--wcs-border);
}

.wcs-card__body {
    padding-block: var(--wds-space-inner);
    padding-inline: var(--wds-space-outer);
}

.wcs-card__ftr {
    padding-block: var(--wds-space-inner);
    padding-inline: var(--wds-space-outer);
    border-block-start: 1px solid var(--wcs-border);
}
```

### Badge

```css
.wcs-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.125rem 0.5rem;
    border-radius: var(--wds-radius-sm);
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.wcs-badge--primary { background-color: var(--primary-100); color: var(--primary-800); }
.wcs-badge--danger  { background-color: var(--danger-100);  color: var(--danger-800);  }
.wcs-badge--success { background-color: var(--success-100); color: var(--success-800); }
.wcs-badge--warning { background-color: var(--warning-100); color: var(--warning-800); }

.dark .wcs-badge--primary { background-color: var(--primary-950); color: var(--primary-200); }
.dark .wcs-badge--danger  { background-color: var(--danger-950);  color: var(--danger-200);  }
.dark .wcs-badge--success { background-color: var(--success-950); color: var(--success-200); }
.dark .wcs-badge--warning { background-color: var(--warning-950); color: var(--warning-200); }
```

### Input

```css
.wcs-inp {
    width: 100%;
    padding: 0.5rem 0.75rem;
    background-color: var(--wcs-surface);
    border: 1px solid var(--wcs-border);
    border-radius: var(--wds-radius-md);
    color: var(--wcs-text);
    font-size: 0.875rem;
    word-spacing: var(--wds-word-spacing);
    letter-spacing: var(--wds-letter-spacing);
}

@media (prefers-reduced-motion: no-preference) {
    .wcs-inp {
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
}

.wcs-inp:focus {
    outline: none;
    border-color: var(--primary-500);
    box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.08);
}

.wcs-inp::placeholder       { color: var(--gray-400); }
.dark .wcs-inp::placeholder { color: var(--gray-500); }

.wcs-inp--error {
    border-color: var(--danger-500);
}
```

---

## Layer 3 — Hooks (`whk-`)

Hook classes carry no styles by default. They exist so that consumers can override layout areas without targeting Filament internals or structural selectors that may change between Filament releases.

```css
/* 05_hooks.css.blade.php — all selectors empty by default */
.whk-sidebar     {}
.whk-navbar      {}
.whk-footer      {}
.whk-main-ctn    {}
.whk-page-hdr    {}
.whk-section     {}
.whk-topbar      {}
.whk-content-wrp {}
```

Sub-package hooks follow `whk-{package}-{element}`:

| Pattern       | Package            |
|---------------|--------------------|
| `whk-sb-*`    | Site Builder       |
| `whk-fm-*`    | Forms              |
| `whk-tbl-*`   | Tables             |
| `whk-ntf-*`   | Notifications      |
| `whk-wgt-*`   | Widgets            |

---

## Layer 4 — Utilities (`wut-`)

Single-purpose. One property per class. No `@apply`. No token references unless the token is the entire point of the class.

```css
.wut-hidden         { display: none; }
.wut-block          { display: block; }
.wut-flex           { display: flex; }
.wut-inline-flex    { display: inline-flex; }
.wut-grid           { display: grid; }
.wut-flex-col       { flex-direction: column; }
.wut-flex-wrap      { flex-wrap: wrap; }
.wut-items-center   { align-items: center; }
.wut-justify-center { justify-content: center; }
.wut-gap-sm         { gap: var(--wds-space-outer); }
.wut-gap-md         { gap: var(--wds-space-inner); }
.wut-overflow-hidden  { overflow: hidden; }
.wut-truncate         { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.wut-pointer          { cursor: pointer; }
.wut-no-pointer       { pointer-events: none; }
.wut-opacity-50       { opacity: 0.5; }
.wut-opacity-75       { opacity: 0.75; }
.wut-opacity-muted    { opacity: 0.65; }
.wut-opacity-disabled { opacity: 0.4; pointer-events: none; }

@media (prefers-reduced-motion: no-preference) {
    .wut-transition        { transition: all 0.2s ease; }
    .wut-transition-colors {
        transition: color 0.2s ease, background-color 0.2s ease, border-color 0.2s ease;
    }
}

.wut-no-ctn {
    width: 100vw;
    max-width: 100vw;
    margin-inline: calc(50% - 50vw);
}

.wut-scrollbar-thin {
    scrollbar-width: thin;
    scrollbar-color: rgba(136, 136, 136, var(--wds-scrollbar-opacity)) transparent;
}
```

---

## Layer 5 — Site Builder (`wsb-`)

Website builder components must render correctly in two distinct contexts: inside the Filament v5 admin panel and in the public-facing output rendered outside the panel.

Rules for `wsb-` classes:

- Never reference Filament panel layout wrappers as implicit ancestors.
- Always use `var(--wcs-*)` for color and `var(--wds-*)` for spacing, radii, and layout.
- Must be visually correct in both `.dark` and light contexts with no additional wrapper dependency.

```css
.wsb-sec {
    position: relative;
    width: 100%;
    padding-block: 4rem;
}

.wsb-sec--hero {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    min-height: 100vh;
}

.wsb-blk {
    background-color: var(--wcs-surface);
    border: 1px solid var(--wcs-border);
    border-radius: var(--wds-radius-content);
    overflow: hidden;
}

.wsb-blk--active {
    outline: 2px solid var(--primary-500);
    outline-offset: 2px;
}

.wsb-ovl-menu {
    position: absolute;
    inset-block-start: 0;
    inset-inline: 0;
    z-index: var(--wds-z-overlay);
}
```

---

## Blade Integration

Our CSS classes are applied inside Blade templates using PHP-driven logic.

```blade
<div
    @class([
        'wcs-card',
        'wsb-blk',
        'wsb-blk--active'      => $block->isSelected(),
        'wut-opacity-disabled' => $block->isLocked(),
        'whk-sb-blk',
        'webkernel-section',
    ])
>
    @if ($block->hasHeader())
        <div class="wcs-card__hdr whk-sb-blk-hdr">
            {{ $block->header }}
        </div>
    @endif
    <div class="wcs-card__body">
        {{ $slot }}
    </div>
</div>
```

The `webkernel-` long-form prefix marks elements that need to be identifiable in markup for JavaScript or for orientation when reading Blade files. It is a semantic label. `wcs-*` carries the styles. `whk-*` is the customization handle.

---

## Filament Override Policy

We target Filament's own class names only in `90_overrides.css.blade.php`. Every override in that file has a comment explaining why it exists.

```css
/* 90_overrides.css.blade.php */

/* Sidebar background — whk-sidebar insufficient here because fi-sidebar
   has a higher specificity base from Filament's own stylesheet */
.fi-sidebar {
    background-color: var(--wcs-surface) !important;
}

/* Code block radius alignment */
.vp-doc div[class*='language-'] {
    border-radius: var(--wds-radius-lg);
}
```

`!important` is only used here and only when a specificity issue cannot be resolved by selector structure. Every use requires a comment.

---

## Definition of Done

A CSS component definition is considered complete only when every item in this checklist is satisfied.

- `whk-{component}` empty selector declared in `05_hooks.css.blade.php`
- Component written in `04_components.css.blade.php` under `wcs-{component}` with BEM suffixes
- All values reference `var(--wcs-*)` or `var(--wds-*)` — no raw hex, px colors, or hardcoded z-index values
- All transitions gated behind `prefers-reduced-motion: no-preference`
- All directional layout uses CSS logical properties
- Dark mode verified with `.dark` on `<html>`
- Light mode verified
- RTL verified with `dir="rtl"` on `<html>`
- Specificity at or below `0-2-0`
- No descendant selectors deeper than two levels
- If used in the website builder: defined in `07_site-builder.css.blade.php` under `wsb-`

---

## Component Authoring Checklist

1. Declare a `whk-{component}` empty selector in `05_hooks.css.blade.php`.
2. Write the component in `04_components.css.blade.php` under `wcs-{component}` with BEM suffixes.
3. Write pure CSS first. Use `@apply` only if composing on a Filament pattern is genuinely cleaner.
4. Reference only `var(--wcs-*)` and `var(--wds-*)` for colors, spacing, radii, and z-index.
5. Use CSS logical properties for all directional layout.
6. Gate all transitions behind `@media (prefers-reduced-motion: no-preference)`.
7. If the component belongs to the website builder, put it in `07_site-builder.css.blade.php` under `wsb-`.
8. Test in light mode, dark mode, inside the Filament admin panel, and in a standalone public page.
9. Test RTL with `dir="rtl"` on `<html>`.
10. Document which modifier classes Blade props emit.
11. Verify specificity does not exceed `0-2-0`.
12. Verify z-index values use tokens from the scale, not hardcoded integers.

---

Numerimondes — Casablanca, Morocco
https://webkernelphp.com/
