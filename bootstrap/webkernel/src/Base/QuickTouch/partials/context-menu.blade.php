{{-- bootstrap/webkernel/backend/quick_touch/quick_touch/partials/context-menu.blade.php --}}
<div id="webkernel-touch-ctx" role="menu" aria-label="Webkernel QuickTouch context menu">

    <button class="wkt-ctx-item" onclick="window.history.back()" role="menuitem">
        <span class="wkt-ctx-item-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>
        </span>
        Go back
    </button>

    <button class="wkt-ctx-item" onclick="window.history.forward()" role="menuitem">
        <span class="wkt-ctx-item-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>
        </span>
        Go forward
    </button>

    <button class="wkt-ctx-item" onclick="window.location.reload()" role="menuitem">
        <span class="wkt-ctx-item-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
        </span>
        Refresh
    </button>

    <div class="wkt-ctx-divider" role="separator"></div>

    <button class="wkt-ctx-item"
            onclick="navigator.clipboard&&navigator.clipboard.writeText(window.location.href)"
            role="menuitem">
        <span class="wkt-ctx-item-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
        </span>
        Copy URL
    </button>

    <div class="wkt-ctx-divider" role="separator"></div>

    <button class="wkt-ctx-item" id="wkt-ctx-add-fav" role="menuitem">
        <span class="wkt-ctx-item-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        </span>
        Add to favorites
    </button>

    {{-- Dynamically injected items from ContextMenuRegistry --}}
    <div id="wkt-ctx-extra" role="group"></div>

    <div class="wkt-ctx-divider" role="separator"></div>

    <button class="wkt-ctx-item" id="wkt-ctx-open-panel" role="menuitem">
        <span class="wkt-ctx-item-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        </span>
        Open QuickTouch
    </button>

</div>
