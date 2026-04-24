<?php declare(strict_types=1);

namespace Webkernel\System\Ops\Contracts;

use Closure;

/**
 * Database-specific schema/DDL operations.
 *
 * DDL is database-specific, not generic across providers.
 * Only DatabaseProvider implements this.
 */
interface DatabaseSchemaProvider
{
    /**
     * Create a table.
     *
     * @param string $table
     * @param Closure $schema Blueprint closure
     */
    public function createTable(string $table, Closure $schema): void;

    /**
     * Alter an existing table.
     *
     * @param string $table
     * @param Closure $schema Blueprint closure
     */
    public function alterTable(string $table, Closure $schema): void;

    /**
     * Drop a table.
     *
     * @param string $table
     * @param bool $ifExists
     */
    public function dropTable(string $table, bool $ifExists = true): void;

    /**
     * Execute raw SQL query.
     *
     * @param string $sql
     * @param array $bindings
     * @return mixed
     */
    public function raw(string $sql, array $bindings = []): mixed;
}
