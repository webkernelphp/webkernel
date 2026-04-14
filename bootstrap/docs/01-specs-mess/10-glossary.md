# 10 — Glossary

**Author:** El Moumen Yassine — Numerimondes
**Platform:** WebKernel PHP

---

| Term | Definition |
|---|---|
| Site | A top-level domain or subdomain managed by the platform. Has its own configuration, apps, and data scope. |
| App | A logical grouping of pages, collections, and routing rules within a site. A site can contain unlimited apps. |
| Collection | A user-defined data model stored as a real database table, scoped to a site and an app. |
| Virtual Collection | A read-only collection whose data comes from an external API, mapped through the API Connector. |
| Field | A typed column definition within a Collection (text, number, date, image, relation, etc.). |
| FieldTypeRegistry | The registry that maps field type identifiers to their FieldContract implementations. |
| Block | A UI element in the page editor (heading, image, button, repeater, form, etc.). |
| BlockRegistry | The registry that maps block type identifiers to their block class and Blade view. |
| DataBindable | The interface that every block implements to optionally accept a data source binding. |
| Variable Mapping | The stored binding between a block element and a specific Collection field. |
| Data Source | The Collection or API source assigned to a block as the origin of its dynamic content. |
| Repeater | A container block that renders its children once per row of a Collection. |
| Rendering Pipeline | The server-side process that converts a page JSON definition into final HTML. |
| Detail Page | An auto-generated page that displays a single record from a Collection, accessed via its identifier slug. |
| Dynamic Route | A URL pattern auto-registered by the system when a Collection is published. |
| DynamicRouteRegistrar | The service that reads published Collections at boot time and registers their routes. |
| Action | A configured event handler attached to an interactive block (navigate, submit, toggle). |
| ActionContract | The PHP interface that all action types implement. |
| API Connector | The tool that allows a user to connect an external REST API and treat its response as a Collection. |
| BlockDefinition | The metadata object registered in the BlockRegistry describing a block type. |
| SchemaBuilder | The service that executes Schema::create and Schema::table calls based on Collection definitions. |
| SiteContract | The foundational interface representing a site within the platform. |
| AppContract | The foundational interface representing an app within a site. |
| CollectionContract | The interface that both local Collections and Virtual Collections implement. |
| FieldContract | The interface that all field type classes implement. |
| BlockRendererContract | The interface for the rendering pipeline that converts blocks to Blade views. |
| VariableMappingContract | The interface for resolving block-to-field bindings at render time. |
| StaticBlockTrait | The trait providing a null implementation of DataBindable for blocks that do not use data. |
| Context | The array of data (typically a single Collection record) passed through the rendering tree. |
| Canvas | The live page preview area in the editor, re-rendered server-side by Livewire after every change. |
| Field Picker | The UI component in the editor's right panel used to select a Collection and a field for binding. |
| Identifier Field | The designated field in a Collection used as the URL slug for detail pages. |
| Metadata Layer | The builder_collections and builder_collection_fields tables that store Collection definitions. |
| Collection Manager | The Filament Resource inside the editor used to create, edit, and manage Collections. |
