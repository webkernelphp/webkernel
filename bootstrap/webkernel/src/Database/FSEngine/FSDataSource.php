<?php
declare(strict_types=1);

namespace Webkernel\Database\FSEngine;

use Illuminate\Database\Eloquent\Model;
use Sushi\Sushi;

/**
 * FSDataSource
 *
 * Abstract base model for all Sushi models backed by FSEngine.
 *
 * Responsibilities:
 *   - Provides a static row-injection contract (setRows / getRows).
 *   - Forces child classes to declare their Sushi schema.
 *   - Serialises array/object fields to JSON before Sushi inserts them into
 *     its in-memory SQLite table, and restores them via $casts on read.
 *     (Sushi uses SQLite which does not understand PHP arrays — everything that
 *      is not a scalar must come in as JSON and be cast back out.)
 *   - Provides resolveRouteBinding() so Filament's {record} route parameter
 *     resolves against the model's actual primary key (even with string PKs
 *     like panel id or connection name) without touching a real database.
 *   - Octane safe: no static write-state shared between requests beyond the
 *     row payload (which is re-injected per request in the List page mount).
 *
 * Concrete subclasses must implement:
 *   - getPrimaryKeyName(): string      — the column used as PK
 *   - getSushiSchema(): array          — column name → Sushi type string
 *   - getJsonColumns(): array          — columns whose values are PHP arrays
 *                                        and must be JSON-encoded for Sushi
 *
 * Usage pattern in Filament List page mount():
 *
 *   ConcreteModel::setRows($mergedRows);
 *
 * then Filament's table queries work normally against the in-memory store.
 */
abstract class FSDataSource extends Model
{
    use Sushi;

    /** @var array<int, array<string, mixed>> */
    private static array $rowStore = [];

    public $incrementing = false;

    // -------------------------------------------------------------------------
    // Abstract contract
    // -------------------------------------------------------------------------

    /**
     * Column name used as primary key (must also appear in getSushiSchema()).
     */
    abstract protected function getPrimaryKeyName(): string;

    /**
     * Sushi column type map.
     * Keys are column names; values are Sushi type strings:
     *   'string' | 'integer' | 'float' | 'boolean'
     *
     * Array-valued columns must be declared as 'string' here (stored as JSON)
     * and listed in getJsonColumns().
     *
     * @return array<string, string>
     */
    abstract protected function getSushiSchema(): array;

    /**
     * Column names whose PHP values are arrays and must be JSON-encoded before
     * Sushi receives them, and JSON-decoded (via $casts) on read.
     *
     * @return list<string>
     */
    protected function getJsonColumns(): array
    {
        return [];
    }

    // -------------------------------------------------------------------------
    // Row injection
    // -------------------------------------------------------------------------

    /**
     * Called by the Filament List page mount() to populate the in-memory store.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    public static function setRows(array $rows): void
    {
        // Use static::class as key so each child class has its own store bucket.
        self::$rowStore[static::class] = $rows;

        // Tell Sushi to rebuild its table with the new rows.
        // Sushi caches its SQLite DB per model; we must clear it whenever rows change.
        $instance = new static();
        
        // If connection exists, drop the table (which might be stale)
        if (static::resolveConnection()) {
            static::resolveConnection()->getSchemaBuilder()->dropIfExists($instance->getTable());
        }

        // Rebuild the table (Sushi's migrate() checks for existence, but we just dropped it if it existed)
        $instance->migrate();
    }

    // -------------------------------------------------------------------------
    // Sushi contract
    // -------------------------------------------------------------------------

    public function getRows(): array
    {
        $rows = self::$rowStore[static::class] ?? [];

        if (empty($rows)) {
            return $rows;
        }

        $jsonCols = $this->getJsonColumns();

        if (empty($jsonCols)) {
            return $rows;
        }

        // JSON-encode array columns so Sushi's SQLite table accepts them.
        return array_map(function (array $row) use ($jsonCols): array {
            foreach ($jsonCols as $col) {
                if (isset($row[$col]) && is_array($row[$col])) {
                    $row[$col] = json_encode($row[$col], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } elseif (array_key_exists($col, $row) && $row[$col] === null) {
                    $row[$col] = null;
                }
            }
            return $row;
        }, $rows);
    }

    public function getSchema(): array
    {
        return $this->getSushiSchema();
    }

    // -------------------------------------------------------------------------
    // Primary key wiring
    // -------------------------------------------------------------------------

    public function getKeyName(): string
    {
        return $this->getPrimaryKeyName();
    }

    protected $keyType = 'string';

    // -------------------------------------------------------------------------
    // Route model binding — makes {record} work for string PKs in Filament
    // -------------------------------------------------------------------------

    /**
     * Resolve a model from a route parameter without a real DB query.
     *
     * Filament's edit/view routes pass the raw PK value as {record}.
     * Eloquent normally runs SELECT ... WHERE id = ? — that works fine when
     * rows are loaded. This override guarantees resolution even when the
     * rowStore was not populated yet (e.g. direct URL access) by triggering
     * a fresh row load through the List page logic.
     */
    public function resolveRouteBinding($value, $field = null): ?static
    {
        // Ensure rows are populated before we query.
        if (empty(self::$rowStore[static::class] ?? [])) {
            static::ensureRowsLoaded();
        }

        $column = $field ?? $this->getPrimaryKeyName();
        return static::where($column, $value)->first();
    }

    /**
     * Hook for child classes to trigger their own row-loading logic when a
     * direct URL access arrives before the List page mount() has run.
     *
     * Override this in the concrete model and call the appropriate repository
     * + setRows() to populate the store.
     */
    protected static function ensureRowsLoaded(): void
    {
        // Default no-op — overriden in concrete models.
    }
}
