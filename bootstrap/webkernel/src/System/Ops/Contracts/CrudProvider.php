<?php declare(strict_types=1);

namespace Webkernel\System\Ops\Contracts;

use Closure;

/**
 * Generic CRUD provider interface for insert, update, delete operations.
 *
 * Implementations: DatabaseProvider, ApiProvider, CustomProvider, FileProvider
 * Any provider that can persist/modify data.
 */
interface CrudProvider extends Provider
{
    /**
     * Insert records into the source.
     *
     * @param array<int, array<string, mixed>> $records
     * @return int Number of affected rows
     */
    public function insert(array $records): int;

    /**
     * Update records matching condition.
     *
     * @param array<string, mixed> $data
     * @param Closure|null $where Condition closure
     * @return int Number of affected rows
     */
    public function update(array $data, ?Closure $where = null): int;

    /**
     * Delete records matching condition.
     *
     * @param Closure|null $where Condition closure
     * @return int Number of deleted rows
     */
    public function delete(?Closure $where = null): int;
}
