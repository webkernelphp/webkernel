<?php

declare(strict_types=1);

namespace Webkernel\Panel\Support;
use Illuminate\Support\Facades\DB;
use Webkernel\Panel\DTO\DatabaseConnectionDTO;
use Webkernel\FSEngine;

/**
 * Repository for database connection config stored in FSEngine.
 *
 * Namespace: "database"
 * Key structure: database.connections.{name} → array
 *
 * On-disk file:  {FSEngine::basePath()}/database.php
 *
 * The traditional Laravel config/database.php file is the developer-owned default.
 * FSEngine overrides sit on top of it at runtime.  When a connection is first
 * "exported" from config/database.php into FSEngine it changes source → 'dynamic'
 * and becomes editable.
 *
 * Password handling:
 *   Passwords are NEVER stored as plain text in FSEngine.
 *   If a password is supplied during a save(), it is encrypted with
 *   Laravel's Crypt facade before storage.  On read, the DTO receives null
 *   for the password field — the UI must treat it as write-only.
 *
 * .env editing:
 *   When a field is env-backed (envMap not empty) the repository provides
 *   writeEnv() to patch the .env file in place.
 */
final class DatabaseConnectionRepository
{
    private const NS  = 'database';
    private const KEY = 'connections';

    // -------------------------------------------------------------------------
    // Read
    // -------------------------------------------------------------------------

    /**
     * Return a single connection DTO by name, or null when not found in FSEngine.
     */
    public static function find(string $name): ?DatabaseConnectionDTO
    {
        $data = FSEngine::get(self::NS . '.' . self::KEY . '.' . $name);

        if (! is_array($data) || empty($data)) {
            return null;
        }

        return DatabaseConnectionDTO::fromArray(array_merge($data, ['name' => $name]));
    }

    /**
     * Return all FSEngine-managed connections as a name-keyed map.
     *
     * @return array<string, DatabaseConnectionDTO>
     */
    public static function all(): array
    {
        $raw  = FSEngine::get(self::NS . '.' . self::KEY) ?? [];
        $dtos = [];

        if (! is_array($raw)) {
            return $dtos;
        }

        foreach ($raw as $name => $data) {
            if (is_array($data)) {
                $dtos[(string) $name] = DatabaseConnectionDTO::fromArray(
                    array_merge($data, ['name' => (string) $name])
                );
            }
        }

        return $dtos;
    }

    /**
     * Return all connections visible to the inspector: FSEngine-managed first,
     * then remaining entries from Laravel's config/database.php.
     *
     * @return array<string, DatabaseConnectionDTO>
     */
    public static function allForInspector(): array
    {
        $managed     = self::all();
        $laravelConns = config('database.connections', []);

        $merged = $managed;

        foreach ($laravelConns as $name => $config) {
            if (! isset($merged[$name]) && is_array($config)) {
                $merged[$name] = DatabaseConnectionDTO::fromLaravelConfig((string) $name, $config);
            }
        }

        return $merged;
    }

    // -------------------------------------------------------------------------
    // Write
    // -------------------------------------------------------------------------

    /**
     * Persist a connection DTO into FSEngine.
     *
     * The password field, if present in the DTO, is encrypted before storage.
     */
    public static function save(DatabaseConnectionDTO $dto): void
    {
        $data = $dto->toArray();

        // Encrypt password before persisting.
        if ($dto->password !== null && $dto->password !== '') {
            $data['password_encrypted'] = encrypt($dto->password);
        }

        unset($data['name']); // name is the key

        FSEngine::set(self::NS . '.' . self::KEY . '.' . $dto->name, $data);
    }

    /**
     * Patch specific fields of an existing connection.
     *
     * Fields listed in DatabaseConnectionDTO::$lockedFields are silently
     * ignored even if passed in $fields.
     *
     * @param array<string, mixed> $fields
     */
    public static function patch(string $name, array $fields): void
    {
        $existing = self::find($name);

        if ($existing === null) {
            return;
        }

        // Remove any fields that are locked on this connection.
        foreach ($existing->lockedFields as $locked) {
            unset($fields[$locked]);
        }

        $merged = array_merge($existing->toArray(), $fields, ['name' => $name]);
        self::save(DatabaseConnectionDTO::fromArray($merged));
    }

    /**
     * Export a static (Laravel-config) connection into FSEngine so it becomes
     * editable.  Does not copy the password.
     */
    public static function export(string $name): ?DatabaseConnectionDTO
    {
        $config = config("database.connections.{$name}");

        if (! is_array($config)) {
            return null;
        }

        $dto = DatabaseConnectionDTO::fromLaravelConfig($name, $config);

        // Promote to dynamic.
        $dto = DatabaseConnectionDTO::fromArray(
            array_merge($dto->toArray(), ['name' => $name, 'source' => 'dynamic'])
        );

        self::save($dto);
        self::invalidateCache();

        return $dto;
    }

    /**
     * Remove a dynamic connection from FSEngine.
     */
    public static function remove(string $name): void
    {
        FSEngine::forget(self::NS . '.' . self::KEY . '.' . $name);
    }

    // -------------------------------------------------------------------------
    // .env editing
    // -------------------------------------------------------------------------

    /**
     * Write or update a key in the application .env file.
     *
     * This modifies the file in place and does NOT call artisan config:cache.
     * The caller is responsible for cache invalidation.
     *
     * Restricted: only keys that actually exist in the current .env file can
     * be written.  This prevents accidental injection.
     *
     * @throws \RuntimeException  When the .env file is not writable or the key
     *                            does not already exist in the file.
     */
    public static function writeEnv(string $envKey, string $value): void
    {
        $path = app()->environmentFilePath();

        if (! file_exists($path) || ! is_writable($path)) {
            throw new \RuntimeException(".env file not found or not writable: {$path}");
        }

        $contents = file_get_contents($path);

        // Safety: only update an existing key, never inject new ones.
        if (! preg_match('/^' . preg_quote($envKey, '/') . '\s*=/m', $contents)) {
            throw new \RuntimeException(
                "Key [{$envKey}] not found in .env — refusing to inject new keys."
            );
        }

        // Quote the value if it contains spaces or special chars.
        $safe = str_contains($value, ' ') || str_contains($value, '#')
            ? '"' . addslashes($value) . '"'
            : $value;

        $updated = preg_replace(
            '/^' . preg_quote($envKey, '/') . '\s*=.*/m',
            $envKey . '=' . $safe,
            $contents,
        );

        file_put_contents($path, $updated, LOCK_EX);
    }

    // -------------------------------------------------------------------------
    // Cache / fingerprint
    // -------------------------------------------------------------------------

    public static function invalidateCache(): void
    {
        FSEngine::invalidate(self::NS);
    }

    public static function fingerprint(): ?string
    {
        return FSEngine::fingerprint(self::NS);
    }


    // -------------------------------------------------------------------------
    // Runtime / Diagnostics
    // -------------------------------------------------------------------------

    /**
     * Test a database connection by attempting a PDO connection.
     *
     * This does NOT mutate Laravel's global config permanently.
     * It builds a temporary connection config and tries to connect.
     *
     * @throws \RuntimeException when connection fails
     */
    public static function test(string $name): void
    {
        // 1. Resolve connection (FSEngine first, fallback Laravel config)
        $dto = self::find($name);

        if ($dto === null) {
            $config = config("database.connections.{$name}");

            if (! is_array($config)) {
                throw new \RuntimeException("Connection [{$name}] not found.");
            }
        } else {
            $config = $dto->toLaravelConfig();
        }

        // 2. Use a temporary connection name to avoid polluting runtime
        $tempConnection = '__test__' . $name . '_' . uniqid();

        config()->set("database.connections.{$tempConnection}", $config);

        try {
            // 3. Purge if exists (Octane-safe)
            DB::purge($tempConnection);

            // 4. Attempt connection
            DB::connection($tempConnection)->getPdo();
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                "Connection test failed for [{$name}]: " . $e->getMessage(),
                previous: $e
            );
        } finally {
            // 5. Cleanup (important for Octane / long-running workers)
            DB::disconnect($tempConnection);
            DB::purge($tempConnection);

            config()->offsetUnset("database.connections.{$tempConnection}");
        }
    }

}
