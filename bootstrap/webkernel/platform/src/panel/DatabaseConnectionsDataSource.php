<?php
declare(strict_types=1);

namespace Webkernel\Panel;

use Webkernel\Database\FSEngine\FSTemplate;
use Webkernel\Panel\Support\DatabaseConnectionRepository;

/**
 * DatabaseConnectionsDataSource
 *
 * In-memory Sushi model for database connection rows.
 *
 * Rows are the union of:
 *   - FSEngine-managed connections (source = 'dynamic')
 *   - Static Laravel config/database.php entries (source = 'static')
 *
 * FSEngine values win for any connection that has been exported/created.
 *
 * All write operations go through DatabaseConnectionRepository.
 * The primary key is the connection name string (e.g. 'mysql', 'sqlite').
 */
class DatabaseConnectionsDataSource extends FSTemplate
{
    protected $primaryKey = 'name';
    protected $keyType    = 'string';

    protected $casts = [
        'is_active'     => 'boolean',
        'locked_fields' => 'array',
        'env_map'       => 'array',
        'options'       => 'array',
    ];

    // -------------------------------------------------------------------------
    // FSDataSource contract
    // -------------------------------------------------------------------------

    protected function getPrimaryKeyName(): string
    {
        return 'name';
    }

    protected function getJsonColumns(): array
    {
        return [
            'locked_fields',
            'env_map',
            'options',
        ];
    }

    protected function getSushiSchema(): array
    {
        return [
            'name'          => 'string',
            'label'         => 'string',
            'driver'        => 'string',
            'is_active'     => 'boolean',
            'source'        => 'string',
            'host'          => 'string',
            'port'          => 'integer',
            'database'      => 'string',
            'username'      => 'string',
            'unix_socket'   => 'string',
            'charset'       => 'string',
            'collation'     => 'string',
            'prefix'        => 'string',
            'schema'        => 'string',
            'ssl_mode'      => 'string',
            // JSON-encoded in Sushi SQLite:
            'locked_fields' => 'string',
            'env_map'       => 'string',
            'options'       => 'string',
        ];
    }

    // -------------------------------------------------------------------------
    // FSTemplate contract
    // -------------------------------------------------------------------------

    protected static function loadInspectorRows(): array
    {
        return collect(DatabaseConnectionRepository::allForInspector())
            ->map(fn ($dto) => $dto->toArray())
            ->values()
            ->all();
    }
}
