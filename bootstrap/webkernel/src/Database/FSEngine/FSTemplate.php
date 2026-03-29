<?php
declare(strict_types=1);

namespace Webkernel\Database\FSEngine;

/**
 * FSTemplate
 *
 * Extends FSDataSource with convenience helpers specific to FSEngine-backed
 * resources that have a defined DTO layer and a repository.
 *
 * Child classes (PanelArraysDataSource, DatabaseConnectionsDataSource) get:
 *   - boilerplate-free getRows() that calls their repository's allForInspector()
 *   - ensureRowsLoaded() that works for direct URL access (edit page without
 *     going through the list first)
 *   - a refreshRows() helper usable from any context
 *
 * Subclasses must implement:
 *   - loadInspectorRows(): array   — calls the repo, returns merged row arrays
 */
abstract class FSTemplate extends FSDataSource
{
    // -------------------------------------------------------------------------
    // Abstract
    // -------------------------------------------------------------------------

    /**
     * Load the full merged row set from the repository.
     * Called both by the List page mount() and by ensureRowsLoaded().
     *
     * @return array<int, array<string, mixed>>
     */
    abstract protected static function loadInspectorRows(): array;

    // -------------------------------------------------------------------------
    // Overrides
    // -------------------------------------------------------------------------

    /**
     * Automatically populate rows when Sushi finds an empty store.
     * This covers direct URL access (edit page, manual browser navigation)
     * without requiring the List page to have been visited first.
     */
    protected static function ensureRowsLoaded(): void
    {
        $rows = static::loadInspectorRows();
        static::setRows($rows);
    }

    // -------------------------------------------------------------------------
    // Public convenience
    // -------------------------------------------------------------------------

    /**
     * Reload rows from the repository and re-inject them into the Sushi store.
     * Call this after any write operation that should be reflected immediately.
     */
    public static function refreshRows(): void
    {
        $rows = static::loadInspectorRows();
        static::setRows($rows);
    }
}
