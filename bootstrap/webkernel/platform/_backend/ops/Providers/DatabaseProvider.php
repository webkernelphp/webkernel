<?php declare(strict_types=1);

namespace Webkernel\System\Ops\Providers;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Webkernel\System\Ops\Contracts\CrudProvider;
use Webkernel\System\Ops\Contracts\DatabaseSchemaProvider;

/**
 * Database provider for CRUD operations via Eloquent or Query Builder.
 *
 * Supports: SELECT, INSERT, UPDATE, DELETE, CREATE TABLE, ALTER TABLE, DROP TABLE, raw SQL.
 *
 * Usage:
 *   webkernel()->do()
 *       ->from(DatabaseProvider::query('users'))
 *       ->filter(fn($u) => $u->active)
 *       ->run();
 *
 *   webkernel()->do()
 *       ->from(DatabaseProvider::query('users'))
 *       ->create(fn($users) => [...])
 *       ->run();
 */
final class DatabaseProvider implements CrudProvider, DatabaseSchemaProvider
{
    public function __construct(
        private readonly Builder|QueryBuilder $query,
        private readonly string $name,
    ) {}

    public static function query(string $table): self
    {
        return new self(
            DB::table($table),
            $table,
        );
    }

    public static function model(string $modelClass): self
    {
        return new self(
            $modelClass::query(),
            class_basename($modelClass),
        );
    }

    /**
     * Apply custom conditions to the query.
     */
    public function where(Closure $conditions): self
    {
        $query = $conditions($this->query);
        return new self($query, $this->name);
    }

    public function fetch(): Collection
    {
        return Collection::make($this->query->get());
    }

    public function insert(array $records): int
    {
        if (empty($records)) {
            return 0;
        }

        return (int) $this->query->insert($records);
    }

    public function update(array $data, ?Closure $where = null): int
    {
        $query = $this->query;

        if ($where !== null) {
            $query = $where($query);
        }

        return $query->update($data);
    }

    public function delete(?Closure $where = null): int
    {
        $query = $this->query;

        if ($where !== null) {
            $query = $where($query);
        }

        return $query->delete();
    }

    public function createTable(string $table, Closure $schema): void
    {
        Schema::create($table, function (Blueprint $table) use ($schema) {
            $schema($table);
        });
    }

    public function alterTable(string $table, Closure $schema): void
    {
        Schema::table($table, function (Blueprint $table) use ($schema) {
            $schema($table);
        });
    }

    public function dropTable(string $table, bool $ifExists = true): void
    {
        $ifExists ? Schema::dropIfExists($table) : Schema::drop($table);
    }

    public function raw(string $sql, array $bindings = []): mixed
    {
        return DB::select($sql, $bindings);
    }

    public function name(): string
    {
        return "Database::{$this->name}";
    }

    public function headers(): array
    {
        // Databases don't have HTTP headers
        return [];
    }
}
