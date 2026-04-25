<?php declare(strict_types=1);

namespace Webkernel\Base\Services;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Webkernel\Base\Databases\Models\DbConnection;

/**
 * DatabaseConnectionResolver — resolves the correct DB connection for a tenant.
 *
 * Cascade resolution order:
 *   1. Module-specific: business_id + module_id record in db_connections
 *   2. Business default: business_id record with module_id IS NULL
 *   3. Instance default: Laravel config('database.default')
 *
 * Each resolved connection is registered dynamically under a stable key so
 * repeated calls within a request re-use the same PDO handle.
 */
class DatabaseConnectionResolver
{
    public function resolve(string $businessId, ?string $moduleId = null): Connection
    {
        $connectionName = $this->connectionName($businessId, $moduleId);

        if (! $this->connectionAlreadyRegistered($connectionName)) {
            $config = $this->buildConfig($businessId, $moduleId);
            Config::set("database.connections.{$connectionName}", $config);
        }

        return DB::connection($connectionName);
    }

    private function connectionName(string $businessId, ?string $moduleId): string
    {
        $suffix = $moduleId ? "{$businessId}_{$moduleId}" : $businessId;
        return "tenant_{$suffix}";
    }

    private function connectionAlreadyRegistered(string $name): bool
    {
        return config("database.connections.{$name}") !== null;
    }

    private function buildConfig(string $businessId, ?string $moduleId): array
    {
        // 1. Module-specific record
        if ($moduleId) {
            $record = DbConnection::query()
                ->where('business_id', $businessId)
                ->where('module_id', $moduleId)
                ->first();

            if ($record) {
                return $record->toLaravelConfig();
            }
        }

        // 2. Business default record
        $record = DbConnection::query()
            ->where('business_id', $businessId)
            ->whereNull('module_id')
            ->first();

        if ($record) {
            return $record->toLaravelConfig();
        }

        // 3. Instance default fallback
        return config('database.connections.' . config('database.default'));
    }
}
