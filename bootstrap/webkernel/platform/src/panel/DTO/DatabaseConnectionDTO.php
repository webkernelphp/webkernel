<?php

declare(strict_types=1);

namespace Webkernel\Panel\DTO;

/**
 * Value object for one database connection entry.
 *
 * Fields can be:
 *   - editable         : normal FSEngine-managed value
 *   - env-backed       : value comes from an .env variable; the DTO carries the
 *                        variable name so the UI can surface it and optionally
 *                        offer to write a new value back to the .env file.
 *   - locked           : field must not be changed through the UI at all
 *                        (e.g. connection names used by the framework internals).
 *
 * The $lockedFields and $envMap arrays are stored in FSEngine alongside the
 * connection data so a panel admin can see them but not circumvent them.
 */
final class DatabaseConnectionDTO
{
    public function __construct(
        // ------------------------------------------------------------------
        // Identity
        // ------------------------------------------------------------------

        /** Connection name as used in config/database.php (e.g. "mysql", "pgsql", "tenant"). */
        public readonly string  $name,

        /** Display label for the UI. */
        public readonly string  $label          = '',

        /** One of: mysql | pgsql | sqlite | sqlsrv | mongodb */
        public readonly string  $driver         = 'mysql',

        /** Whether this connection is active / usable. */
        public readonly bool    $isActive        = true,

        /** Source of truth: 'static' (config file only) | 'dynamic' (FSEngine-managed). */
        public readonly string  $source          = 'dynamic',

        // ------------------------------------------------------------------
        // Connection params
        // ------------------------------------------------------------------
        public readonly string  $host            = '127.0.0.1',
        public readonly int     $port            = 3306,
        public readonly string  $database        = '',
        public readonly string  $username        = '',

        /**
         * Password is NEVER stored in FSEngine in plain text.
         * The DTO carries it only during a save cycle; persistence stores
         * either an env-variable reference or an encrypted value.
         * The UI must treat this field as write-only (no read-back).
         */
        public readonly ?string $password        = null,

        /** Unix socket path; when set, host/port are ignored. */
        public readonly ?string $unixSocket      = null,

        /** Database charset (mysql: utf8mb4). */
        public readonly string  $charset         = 'utf8mb4',

        /** Database collation (mysql: utf8mb4_unicode_ci). */
        public readonly string  $collation       = 'utf8mb4_unicode_ci',

        /** Table name prefix. */
        public readonly string  $prefix          = '',

        /** Schema (pgsql). */
        public readonly string  $schema          = 'public',

        /** SSL mode (pgsql: prefer | require | disable). */
        public readonly ?string $sslMode         = null,

        // ------------------------------------------------------------------
        // Locking and env-variable mapping
        // ------------------------------------------------------------------

        /**
         * Field names that must not be edited through the UI.
         * Example: ['name', 'driver'] for a core connection.
         *
         * @var string[]
         */
        public readonly array   $lockedFields    = [],

        /**
         * Map of field name → .env variable name for fields whose current
         * value is sourced from the environment.
         * Example: ['password' => 'DB_PASSWORD', 'host' => 'DB_HOST']
         *
         * When a field appears here, the UI shows the env variable name
         * instead of the value, and offers an "Edit .env" action.
         *
         * @var array<string, string>
         */
        public readonly array   $envMap          = [],

        // ------------------------------------------------------------------
        // Extra / driver-specific options
        // ------------------------------------------------------------------

        /**
         * Arbitrary key → value map for driver-specific options not covered
         * by the standard fields (e.g. mysql options[], pgsql application_name).
         *
         * @var array<string, mixed>
         */
        public readonly array   $options         = [],
    ) {}

    // -------------------------------------------------------------------------
    // Construction helpers
    // -------------------------------------------------------------------------

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $b = static fn (mixed $v): bool    => (bool)   $v;
        $s = static fn (mixed $v): ?string => ($v !== null && $v !== '') ? (string) $v : null;

        return new self(
            name:         (string)  ($data['name']          ?? ''),
            label:        (string)  ($data['label']         ?? ''),
            driver:       (string)  ($data['driver']        ?? 'mysql'),
            isActive:     $b($data['is_active']              ?? true),
            source:       (string)  ($data['source']        ?? 'dynamic'),

            host:         (string)  ($data['host']          ?? '127.0.0.1'),
            port:         (int)     ($data['port']          ?? 3306),
            database:     (string)  ($data['database']      ?? ''),
            username:     (string)  ($data['username']      ?? ''),
            password:     $s($data['password']               ?? null),
            unixSocket:   $s($data['unix_socket']            ?? $data['unixSocket'] ?? null),
            charset:      (string)  ($data['charset']       ?? 'utf8mb4'),
            collation:    (string)  ($data['collation']     ?? 'utf8mb4_unicode_ci'),
            prefix:       (string)  ($data['prefix']        ?? ''),
            schema:       (string)  ($data['schema']        ?? 'public'),
            sslMode:      $s($data['ssl_mode']               ?? $data['sslMode'] ?? null),

            lockedFields: is_array($data['locked_fields']   ?? null) ? $data['locked_fields']  : [],
            envMap:       is_array($data['env_map']         ?? null) ? $data['env_map']         : [],
            options:      is_array($data['options']         ?? null) ? $data['options']         : [],
        );
    }

    /**
     * Snapshot the current live Laravel database.connections config entry into
     * a DTO, marking all env-backed fields automatically.
     *
     * @param array<string, mixed> $config  e.g. config('database.connections.mysql')
     */
    public static function fromLaravelConfig(string $name, array $config): self
    {
        // Detect which fields are env-backed by checking whether the raw .env
        // value matches the resolved config value (heuristic: env() !== null).
        $envMap = [];

        $envCandidates = [
            'host'     => 'DB_HOST',
            'port'     => 'DB_PORT',
            'database' => 'DB_DATABASE',
            'username' => 'DB_USERNAME',
            'password' => 'DB_PASSWORD',
        ];

        foreach ($envCandidates as $field => $envKey) {
            if (env($envKey) !== null) {
                $envMap[$field] = $envKey;
            }
        }

        return new self(
            name:      $name,
            label:     ucfirst($name),
            driver:    (string) ($config['driver']    ?? 'mysql'),
            isActive:  true,
            source:    'static',

            host:      (string) ($config['host']      ?? '127.0.0.1'),
            port:      (int)    ($config['port']      ?? 3306),
            database:  (string) ($config['database']  ?? ''),
            username:  (string) ($config['username']  ?? ''),
            password:  null,    // never expose password on read
            charset:   (string) ($config['charset']   ?? 'utf8mb4'),
            collation: (string) ($config['collation'] ?? 'utf8mb4_unicode_ci'),
            prefix:    (string) ($config['prefix']    ?? ''),
            schema:    (string) ($config['schema']    ?? 'public'),

            // Core connections introspected from config() are locked for name+driver.
            lockedFields: ['name', 'driver'],
            envMap:       $envMap,
        );
    }

    // -------------------------------------------------------------------------
    // Serialisation
    // -------------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name'          => $this->name,
            'label'         => $this->label,
            'driver'        => $this->driver,
            'is_active'     => (int) $this->isActive,
            'source'        => $this->source,

            'host'          => $this->host,
            'port'          => $this->port,
            'database'      => $this->database,
            'username'      => $this->username,
            // password intentionally omitted — never round-trip it through FSEngine
            'unix_socket'   => $this->unixSocket,
            'charset'       => $this->charset,
            'collation'     => $this->collation,
            'prefix'        => $this->prefix,
            'schema'        => $this->schema,
            'ssl_mode'      => $this->sslMode,

            'locked_fields' => $this->lockedFields,
            'env_map'       => $this->envMap,
            'options'       => $this->options,
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isLocked(string $field): bool
    {
        return in_array($field, $this->lockedFields, true);
    }

    public function envVar(string $field): ?string
    {
        return $this->envMap[$field] ?? null;
    }

    public function isEnvBacked(string $field): bool
    {
        return isset($this->envMap[$field]);
    }

    // -------------------------------------------------------------------------
    // Runtime conversion
    // -------------------------------------------------------------------------

    /**
     * Convert DTO into a Laravel database connection config array.
     *
     * This is used for runtime connection testing or dynamic connections.
     *
     * IMPORTANT:
     * - Password is resolved from env if env-backed
     * - Otherwise, encrypted password is NOT returned (write-only)
     *
     * @return array<string, mixed>
     */
    public function toLaravelConfig(): array
    {
        // Resolve password
        $password = null;

        if ($this->isEnvBacked('password')) {
            $envKey = $this->envVar('password');
            $password = $envKey ? env($envKey) : null;
        }

        return array_filter([
            'driver'   => $this->driver,
            'host'     => $this->host,
            'port'     => $this->port,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $password,
            'unix_socket' => $this->unixSocket,
            'charset'  => $this->charset,
            'collation'=> $this->collation,
            'prefix'   => $this->prefix,
            'schema'   => $this->schema,
            'sslmode'  => $this->sslMode,

            // driver-specific
            ...$this->options,
        ], static fn ($v) => $v !== null);
    }
}
