# Webkernel HTML Component Specification

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

## Scope

This document specifies how Webkernel components are structured, where they live, how they integrate with Filament v5 (forms, widgets, tables, panels), and what constraints every component must satisfy — accessibility, RTL, dyslexia-friendliness, behavioral consistency, security, and performance across contexts.

A Webkernel component is any reusable UI unit that may appear in more than one of the following contexts:

- A Filament form field or form layout
- A Filament widget (stats, charts, tables)
- A Filament page or resource page
- The Webkernel website builder (block editor, section renderer, public output)
- A standalone Blade partial rendered outside the panel

Every component must work correctly in all contexts it targets without context-specific branches inside the component itself. Context-specific behavior is handled by the injection layer, not the component.

---

## Core Principles

**Single source, multiple surfaces.** A component is defined once. It is not forked for the website builder, not reimplemented for a widget, not copied for a form. The same Blade partial, the same CSS class, the same behavior everywhere.

**Filament integration is natural, not bolted on.** Components integrate with Filament through the standard extension points: `ViewField`, `ViewEntry`, custom widgets, render hooks. We do not patch Filament internals. We do not override Filament's Blade views.

**Accessibility is structural, not decorative.** ARIA attributes, keyboard navigation, focus management, and semantic HTML are part of the component contract, not an afterthought. A component that is visually complete but inaccessible is not done.

**RTL is a first-class layout requirement.** Every component is designed with bidirectional layout in mind from the start. RTL is not a patch applied after the fact.

**Readability is a design constraint.** Typography choices, contrast ratios, spacing, and line length limits are specified to support users with dyslexia and other reading disabilities. These constraints benefit all users.

---

## Component File Structure

Each component is a self-contained directory. The structure below is canonical. Locations are relative to the package root and will be defined precisely per deployment context.

```
[component-name]/
├── partials/
│   ├── Default/
│   │   ├── Default[ComponentName].php
│   │   └── view.blade.php
│   ├── Simple/
│   │   └── view.blade.php
│   └── Slider/
│       ├── Slider[ComponentName].php
│       └── view.blade.php
├── LICENCE
├── Simple[ComponentName]Block.php
├── thumbnail.png
└── view.blade.php
```

Each component has its own namespace, callable independently. Components may originate from first-party, second-party, or third-party modules. The namespace resolution layer handles collisions and loading priority. Specific filesystem paths for each deployment context are defined in the deployment documentation, not here.

### Component Artifacts

Every Webkernel component consists of four mandatory artifacts.

**1. The Blade Partial**

The partial is the only place where HTML structure is defined. It emits the correct semantic HTML, ARIA attributes, and CSS classes. It accepts props. It exposes a default slot and named slots where composition is needed.

**2. The CSS Definition**

Written as pure CSS under a `wcs-` prefix following the rules in the CSS specification. No styles live in the Blade partial itself.

**3. The Hook Selector**

An empty `whk-` selector paired with each component, allowing downstream consumers to customize the component surface without touching its definition.

**4. The Filament Integration Point**

A PHP class or trait that registers the component into the Filament surface where it is used. This may be a custom `ViewField`, a `Widget` class, a `ViewEntry`, or a render hook callback. Documented per component.

---

## Versioning and Backward Compatibility

Webkernel components follow semantic versioning. Stability guarantees are tied to version numbers.

**Breaking changes require a major version increment:**

- Removing a prop
- Renaming a prop
- Changing the default value of a prop in a way that alters rendered output
- Renaming a `wcs-` CSS class
- Renaming a `whk-` hook class
- Changing the DOM structure in a way that breaks selector-based customizations
- Changing default ARIA behavior
- Changing a slot name

**Non-breaking changes (minor version):**

- Adding a new optional prop with a backward-compatible default
- Adding a new modifier class
- Adding a new `whk-` hook
- Adding a new named slot
- Internal implementation changes that do not affect the public class surface

**Patch changes:**

- Bug fixes that restore documented behavior
- Accessibility fixes that do not change the class surface

When a class is renamed, the old name must be supported for one major version cycle and emit a deprecation notice via a Blade `@php trigger_error(...)` or equivalent. The deprecation period is documented in the changelog.

---

## HTML Structure Rules

### Semantic Elements First

We use the correct HTML element for the job. We do not use `<div>` when a semantic alternative exists.

| Use case             | Element                                                    |
|----------------------|------------------------------------------------------------|
| Navigation           | `<nav>`                                                    |
| Main content region  | `<main>`                                                   |
| Complementary aside  | `<aside>`                                                  |
| Grouped form fields  | `<fieldset>` + `<legend>`                                  |
| Section with heading | `<section>` + `<h2>` through `<h6>`                       |
| Independent article  | `<article>`                                                |
| Data table           | `<table>` + `<caption>` + `<thead>` + `<tbody>`            |
| Interactive button   | `<button type="button">`                                   |
| Linked action        | `<a href="...">`                                           |
| Status message       | `<p role="status">` or `<div role="alert">`               |

We never use `<div role="button">` when `<button>` is available. We never use `<span>` as an interactive element.

### Heading Hierarchy

Every component that contains a heading must accept the heading level as a prop. The default level is `h3` since components are typically embedded inside page sections that already establish `h1` and `h2`. The correct level is the consumer's responsibility.

```blade
{{-- card.blade.php --}}
@props([
    'title'        => null,
    'headingLevel' => 'h3',
])
@if ($title)
    <{{ $headingLevel }} class="wcs-card__title">{{ $title }}</{{ $headingLevel }}>
@endif
```

### Landmark Regions

- `<nav>` elements receive `aria-label` describing which navigation they represent.
- Sidebars rendered as `<aside>` receive `aria-label`.
- Search regions receive `role="search"`.
- Notification areas receive `role="status"` for polite updates or `role="alert"` for urgent messages.

### Lists

Navigation menus, option lists, tag lists, and breadcrumbs are always marked up as `<ul>` or `<ol>`. A visual list that is not a semantic list is a markup error.

---

## Composition Rules

Components may be composed freely. The following rules prevent hidden coupling.

- A component may nest other components.
- A component must not depend on being inside a specific parent element. It must function correctly when rendered in isolation.
- Layout responsibility belongs to layout components, not leaf components. A `wcs-card` does not position itself; its container does.
- A component must not query ancestor elements for behavior. No `closest()`, no implicit parent assumptions.
- Slot content is the consumer's responsibility. A component defines the slot; it does not constrain what the consumer places inside it.

---

## Behavioral Layer (JavaScript)

- Behavior must never mutate structural classes (`wcs-*`).
- Behavior attaches via `data-wk-*` attributes on the element. The `data-wk-*` namespace is the behavioral contract.
- No inline JavaScript in Blade files. No `onclick`, `onmouseover`, or similar inline handlers anywhere.
- JavaScript modules live in `resources/js/webkernel/`. Each module handles one behavioral concern.
- Alpine.js is allowed as a thin behavioral layer for simple toggle, show/hide, and dropdown behavior. It must not manage application state.
- Livewire dispatch events must never assume DOM structure. Events communicate intent; the receiver handles the DOM consequence.
- Behavioral classes (`is-open`, `is-active`) used exclusively by JavaScript are prefixed `wk-is-*` and are never referenced in CSS component files.

---

## Accessibility Requirements

### Keyboard Navigation

Every interactive component must be fully operable by keyboard alone.

| Interaction                   | Required keyboard behavior                                              |
|-------------------------------|-------------------------------------------------------------------------|
| Button                        | `Enter` and `Space` activate                                            |
| Link                          | `Enter` activates                                                       |
| Dropdown / menu               | `ArrowUp` / `ArrowDown` navigate, `Enter` selects, `Escape` closes     |
| Modal / dialog                | `Escape` closes, focus trapped inside while open                        |
| Tabs                          | `ArrowLeft` / `ArrowRight` navigate between tabs                        |
| Checkbox / radio              | `Space` toggles, arrow keys navigate within group                       |
| Combobox / autocomplete       | `ArrowUp` / `ArrowDown` navigate suggestions, `Enter` selects, `Escape` dismisses |

Focus must be visible at all times. We do not suppress the browser's default focus ring without providing an equal or better replacement. Our global focus style:

```css
:focus-visible {
    outline: 2px solid var(--primary-500);
    outline-offset: 2px;
}
```

Individual components do not override this unless they provide a demonstrably better equivalent.

### Focus Management

When a modal or panel opens, focus moves to the first focusable element inside it, or to the dialog heading. When it closes, focus returns to the element that triggered it.

When a dynamic section of the page updates (a form step advances, a notification appears, a filter is applied), we use an `aria-live` region to communicate the change to screen readers without requiring focus movement.

```blade
{{-- Injected once per panel layout via render hook --}}
<div
    role="status"
    aria-live="polite"
    aria-atomic="true"
    class="wut-hidden"
    id="wk-live-region"
></div>
```

Livewire components announce changes by dispatching to this region:

```php
$this->dispatch('wk-announce', message: __('Record saved successfully.'));
```

### ARIA Labels and Descriptions

- Every icon-only button receives `aria-label`.
- Every input is associated with its label via `for`/`id` pairing. We do not use `aria-label` on inputs as a substitute for a visible label.
- Every input that has description or hint text associates it via `aria-describedby`.
- Every input in an error state receives `aria-invalid="true"` and `aria-describedby` pointing to the error message element.
- Every modal receives `role="dialog"`, `aria-modal="true"`, and `aria-labelledby` pointing to its heading.
- Loading states are communicated via `aria-busy="true"` on the containing region.

### Color and Contrast

Minimum contrast ratios follow WCAG 2.1 AA:

| Context                                    | Minimum ratio |
|--------------------------------------------|---------------|
| Normal text (below 18px / 14px bold)       | 4.5:1         |
| Large text (18px+ regular / 14px+ bold)    | 3:1           |
| UI components and focus indicators         | 3:1           |
| Decorative elements                        | No requirement |

We never use color alone to convey information. Status indicators (success, warning, danger) always combine color with an icon or a text label.

---

## Accessibility Testing Requirements

Every component must pass all of the following before it is considered complete:

- Keyboard-only navigation audit through all interactive states
- Screen reader reading order test (VoiceOver on macOS/iOS, NVDA on Windows)
- Lighthouse accessibility score of 95 or above
- RTL layout snapshot test with `dir="rtl"` on `<html>`
- Dark mode snapshot test with `.dark` on `<html>`
- Reduced motion snapshot test with `prefers-reduced-motion: reduce`
- Relaxed reading mode snapshot test with `data-wk-reading-mode="relaxed"` on a parent element

These tests are not optional. A component that has not passed all of them is not done.

---

## Dyslexia and Reading Accessibility

These are not optional enhancements. They are part of the base specification for every component that contains prose or user-generated text.

### Typography Constraints

- Base font size: minimum `1rem` (16px). We never set body text below `1rem`.
- Line height: minimum `1.5` for body text. Form labels and helper text use `1.4` minimum.
- Line length: maximum `75ch` for any block of prose. Components that render user content clamp at `75ch`.
- Letter spacing: normal tracking for body text. We do not tighten letter spacing below the browser default.
- Font weight: regular text at `400`. We do not use weights below `400` for body text. Bold emphasis at `600` or `700`.
- Font family: inherited from the panel configuration. We do not override font families inside individual components except for monospace code content.
- Text alignment: never `justify`. Justified text creates uneven word spacing that significantly impairs reading for dyslexic users.
- All-caps: never applied to body text or paragraphs. Badge and label components that use uppercase restrict it to short strings of two to four words.

### Relaxed Reading Mode

We expose a reading mode activated by setting `data-wk-reading-mode="relaxed"` on `<html>` or any ancestor element. This increases word and letter spacing for users who benefit from it.

```css
:root {
    --wds-word-spacing:   normal;
    --wds-letter-spacing: normal;
}
[data-wk-reading-mode="relaxed"] {
    --wds-word-spacing:   0.16em;
    --wds-letter-spacing: 0.12em;
}
.wcs-prose,
.wcs-inp,
.wcs-card__body,
.wcs-alert__body {
    word-spacing:   var(--wds-word-spacing);
    letter-spacing: var(--wds-letter-spacing);
}
```

The toggle is a single HTML attribute write. No JavaScript framework dependency. It can be driven by a user preference stored in the database and emitted by a Blade `@if` in the layout template.

### Paragraph and Section Spacing

Adjacent paragraphs are separated by `1em` margin. Sections within a component have a minimum `1.5rem` gap. Vertical rhythm is never collapsed below readable thresholds.

---

## RTL Support

### The Rule

Every component is RTL-compatible at authoring time. RTL support is never retrofitted.

### CSS Logical Properties

We use CSS logical properties for all directional layout. Physical properties (`left`, `right`, `margin-left`, `padding-right`) are replaced with logical equivalents.

```css
/* Do not use for directional layout */
.wcs-card__hdr {
    padding-left: 1rem;
    border-left: 3px solid var(--primary-500);
}

/* Correct */
.wcs-card__hdr {
    padding-inline: 1rem;
    border-inline-start: 3px solid var(--primary-500);
}
```

Physical properties are only used when the style is genuinely non-directional — vertical padding, border-radius, box-shadow.

### The `dir` Attribute

RTL layout is activated by `dir="rtl"` on `<html>` or a containing element. Filament v5 sets `dir` at the panel level via its locale configuration. Our components inherit from it automatically through logical properties.

Components that accept an explicit `$rtl` prop apply `dir="rtl"` to their root element for contexts where they are rendered outside the panel without an inherited direction.

```blade
@props(['rtl' => false])
<div
    class="wcs-card whk-card"
    @if ($rtl) dir="rtl" @endif
>
    {{ $slot }}
</div>
```

### Directional Icons

Icons that indicate direction (chevrons, arrows, back/forward) are flipped in RTL via CSS:

```css
[dir="rtl"] .wcs-icon--directional {
    transform: scaleX(-1);
}
```

Non-directional icons (checkmarks, warning symbols, logos) must not be flipped.

### Numbers, Code, and Technical Identifiers

Numbers, code snippets, URLs, email addresses, file paths, and technical identifiers are always rendered with explicit LTR direction to prevent bidi reordering:

```blade
<span class="wcs-code" dir="ltr">{{ $value }}</span>
```

### RTL Mirroring Reference

| Element                       | Mirror in RTL? |
|-------------------------------|----------------|
| Sidebar position              | Yes            |
| Breadcrumb arrow              | Yes            |
| Navigation chevron            | Yes            |
| Back button arrow             | Yes            |
| Progress bar fill direction   | Yes            |
| Checkmark icon                | No             |
| Warning / info icon           | No             |
| Logo                          | No             |
| Number / code / date / URL    | No — force LTR |

---

## Security Constraints

These rules are mandatory. There are no exceptions.

- User-generated content must be sanitized before rendering. Use a vetted sanitizer library. Sanitization happens at the service layer before data reaches the component.
- Blade `{!! !!}` (unescaped output) is forbidden unless the content has been explicitly sanitized and the variable name communicates that fact (e.g., `$sanitizedHtml`).
- No inline event handlers. No `onclick`, `onmouseover`, `onfocus`, or any other inline event attribute anywhere in any Blade partial.
- No dynamic class names generated from user input. Class names must be derived from a validated whitelist.
- No `href` or `src` attributes that directly interpolate unsanitized user values. URL attributes must be validated against an allowlist of schemes.
- Content Security Policy headers must not be loosened to accommodate a component. If a component requires `unsafe-inline`, the component is wrong.

---

## Performance Constraints

- No component may trigger layout thrashing. DOM measurements and DOM mutations must not be interleaved. Measure first, then mutate.
- No infinite CSS selectors. No selectors that could match an unbounded number of elements through descendant combinators.
- Avoid deep descendant selectors. No selector chain longer than two levels inside a component's own scope.
- Components must not exceed three levels of nesting where avoidable. Deep nesting is a structural smell that should be resolved by composition, not accepted.
- No runtime DOM measurements to drive layout. Layout is the browser's job. We do not reimplement it.
- No synchronous `localStorage` or `sessionStorage` access in the critical rendering path.
- Components do not load external resources (fonts, scripts, stylesheets) independently. Resource loading is the application shell's responsibility.

---

## States and CSS Modifiers

Every interactive component expresses its state through modifier classes, not inline styles or JavaScript-only attribute changes.

| State     | CSS modifier class           | HTML attribute          |
|-----------|------------------------------|-------------------------|
| Disabled  | `wcs-{component}--disabled`  | `aria-disabled="true"`  |
| Loading   | `wcs-{component}--loading`   | `aria-busy="true"`      |
| Error     | `wcs-{component}--error`     | `aria-invalid="true"`   |
| Selected  | `wcs-{component}--selected`  | `aria-selected="true"`  |
| Expanded  | `wcs-{component}--expanded`  | `aria-expanded="true"`  |
| Active    | `wcs-{component}--active`    | `aria-current="true"`   |

States are never communicated by color alone. A disabled input is also visually desaturated with reduced opacity. An error input shows a color change, an icon, and a text message. All three together, never any one alone.

### State Synchronization

State must be reflected simultaneously in both the modifier class and the corresponding ARIA attribute. JavaScript must never toggle a visual modifier class without updating the matching ARIA attribute in the same operation.

```javascript
// Correct
element.classList.add('wcs-btn--loading');
element.setAttribute('aria-busy', 'true');

// Wrong — visual state and accessible state are now out of sync
element.classList.add('wcs-btn--loading');
```

---

## Standardized Props

Every component declares its props explicitly with `@props`. The following props are standardized across all components that support them:

| Prop              | Type     | Default     | Purpose                                                        |
|-------------------|----------|-------------|----------------------------------------------------------------|
| `headingLevel`    | `string` | `'h3'`      | HTML heading element level for the component's title           |
| `rtl`             | `bool`   | `false`     | Explicitly sets `dir="rtl"` on the component root             |
| `id`              | `string` | `null`      | HTML `id` for the component root                              |
| `ariaLabel`       | `string` | `null`      | `aria-label` when no visible label exists                     |
| `ariaDescribedBy` | `string` | `null`      | `aria-describedby` value                                      |
| `loading`         | `bool`   | `false`     | Loading state — adds `aria-busy`, renders skeleton or spinner  |
| `disabled`        | `bool`   | `false`     | Disabled state — adds `aria-disabled="true"`                  |
| `variant`         | `string` | `'default'` | Visual variant: primary, danger, success, warning, ghost      |

---

## Motion and Animation

We respect `prefers-reduced-motion`. All transitions and animations are gated:

```css
.wcs-btn {
    /* No transition by default */
}

@media (prefers-reduced-motion: no-preference) {
    .wcs-btn {
        transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }
}
```

Animation durations: functional transitions use `150ms` to `200ms`. Visual transitions use `250ms` to `350ms` maximum. Nothing animates longer than `500ms` unless the animation is itself the content (loaders, progress).

---

## Filament v5 Integration

### Forms — ViewField

```php
use Filament\Forms\Components\ViewField;

ViewField::make('content')
    ->view('webkernel::components.rich-editor')
    ->viewData([
        'rtl' => in_array(app()->getLocale(), config('webkernel.rtl_locales', [])),
    ]);
```

### Forms — ViewEntry (Infolists)

```php
use Filament\Infolists\Components\ViewEntry;

ViewEntry::make('status')
    ->view('webkernel::components.badge')
    ->viewData(['variant' => 'success']);
```

### Widgets

```php
namespace Webkernel\Widgets;

use Filament\Widgets\Widget;

class StatSummary extends Widget
{
    protected static string $view = 'webkernel::widgets.stat-summary';
    protected int|string|array $columnSpan = 'full';
}
```

### Table Columns

```php
use Filament\Tables\Columns\ViewColumn;

ViewColumn::make('status')
    ->view('webkernel::components.table-badge');
```

### Render Hooks for Global Components

```php
FilamentView::registerRenderHook(
    PanelsRenderHook::TOPBAR_END,
    fn () => view('webkernel::components.topbar-actions', [
        'rtl' => in_array(app()->getLocale(), config('webkernel.rtl_locales', [])),
    ]),
);
```

### Dark Mode

Components receive dark mode automatically through `var(--wcs-*)` color tokens. The `.dark` class is set on `<html>` by Filament's own theme toggle. No component-level JavaScript or dark mode detection is needed inside the component.

### Filament Component Wrapper Trait

```php
namespace Webkernel\Concerns;

trait InteractsWithWebkernelComponent
{
    protected string $wkVariant = 'default';
    protected bool   $wkRtl    = false;

    public function variant(string $variant): static
    {
        $this->wkVariant = $variant;
        return $this;
    }

    public function rtl(bool $condition = true): static
    {
        $this->wkRtl = $condition;
        return $this;
    }

    public function getWkViewData(): array
    {
        return [
            'variant' => $this->wkVariant,
            'rtl'     => $this->wkRtl,
        ];
    }
}
```

---

## Website Builder Specifics

Components used in the website builder are additionally registered as builder blocks. Each block has:

- A `wsb-` CSS class for its structural container.
- A `whk-sb-*` hook class for customization.
- A block definition class that declares its name, icon, schema (editable fields), and preview.
- A rendered output that is identical whether viewed in the admin panel preview or the public-facing page.

```blade
{{-- webkernel/components/wsb-block-wrapper.blade.php --}}
@props([
    'editing'  => false,
    'selected' => false,
    'blockId'  => null,
    'rtl'      => false,
])
<div
    @class([
        'wsb-blk',
        'whk-sb-blk',
        'wsb-blk--active'  => $selected,
        'wsb-blk--editing' => $editing,
    ])
    @if ($rtl)      dir="rtl"                        @endif
    @if ($editing)  data-wk-block-id="{{ $blockId }}" @endif
    @if ($selected) aria-current="true"               @endif
>
    {{ $slot }}
</div>
```

---

## Definition of Done

A component is considered complete only when every item in this checklist is satisfied. Partial completion is not done.

- Blade structure finalized and props declared with `@props`
- All visual values reference CSS tokens only — no raw hex, px, or named colors
- RTL layout verified with `dir="rtl"` on `<html>`
- Dark mode verified with `.dark` on `<html>`
- Reduced motion verified with `prefers-reduced-motion: reduce`
- Relaxed reading mode verified with `data-wk-reading-mode="relaxed"`
- ARIA attributes audited for all states and interactive behaviors
- Keyboard-only navigation tested through all interactive states
- Screen reader reading order verified
- Lighthouse accessibility score of 95 or above
- Hook class (`whk-*`) declared in `05_hooks.css.blade.php`
- Filament integration point documented and implemented
- Security constraints verified: no raw `{!! !!}`, no inline handlers, no dynamic class names from user input
- Versioning impact assessed: breaking changes documented, deprecation notices added if applicable
- If used in the website builder: `wsb-` and `whk-sb-*` classes present and block definition class registered

---

## Component Authoring Checklist

1. Define the Blade partial. Declare all props explicitly with `@props`.
2. Use semantic HTML. Choose the correct element for the job.
3. Declare ARIA attributes for every interactive or dynamic behavior.
4. Write the CSS definition under `wcs-{component}` with BEM suffixes. Use only `var(--wcs-*)` and `var(--wds-*)` for all visual values.
5. Add an empty `whk-{component}` selector in the hooks file.
6. Use CSS logical properties for all directional layout. Flag directional icons with `wcs-icon--directional`.
7. Force `dir="ltr"` on all numbers, codes, URLs, and technical identifiers.
8. Gate all transitions behind `@media (prefers-reduced-motion: no-preference)`.
9. Enforce minimum font size `1rem`, line height `1.5`, maximum line length `75ch` for any prose content. Never use `text-align: justify` or `text-transform: uppercase` on body text.
10. Register the Filament integration point.
11. If used in the website builder, add `wsb-` and `whk-sb-*` classes and register a block definition class.
12. Apply all security constraints. No `{!! !!}` without sanitization. No inline event handlers. No dynamic class names from user input.
13. Run the full Definition of Done checklist.

---

Numerimondes — Casablanca, Morocco
https://webkernelphp.com/
