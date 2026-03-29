<?php

declare(strict_types=1);

namespace Webkernel\Panel\Support;

use Webkernel\Panel\DTO\PanelDTO;
use Webkernel\Panel\Store\PanelConfigStore;

/**
 * Repository for panel configuration.
 *
 * Delegates to PanelConfigStore (JSON files in storage/webkernel/panels/).
 * Static interface preserved so PanelArraysDataSource can call it without
 * constructor injection.
 */
final class PanelConfigRepository
{
    // -------------------------------------------------------------------------
    // Read
    // -------------------------------------------------------------------------

    public static function find(string $id): ?PanelDTO
    {
        $data = self::store()->find($id);

        if ($data === null) {
            return null;
        }

        return PanelDTO::fromArray(array_merge($data, ['id' => $id]));
    }

    /**
     * @return array<string, PanelDTO>
     */
    public static function all(): array
    {
        $dtos = [];

        foreach (self::store()->all() as $id => $data) {
            $dtos[(string) $id] = PanelDTO::fromArray(array_merge($data, ['id' => (string) $id]));
        }

        return $dtos;
    }

    // -------------------------------------------------------------------------
    // Write
    // -------------------------------------------------------------------------

    public static function save(PanelDTO $dto): void
    {
        $data = $dto->toArray();
        unset($data['id']);

        self::store()->save($dto->id, $data);
    }

    /**
     * @param array<string, mixed> $fields
     */
    public static function patch(string $id, array $fields): void
    {
        $existing = self::find($id);

        if ($existing === null) {
            return;
        }

        $merged = array_merge($existing->toArray(), $fields, ['id' => $id]);
        self::save(PanelDTO::fromArray($merged));
    }

    public static function remove(string $id): void
    {
        self::store()->remove($id);
    }

    // -------------------------------------------------------------------------
    // Cache
    // -------------------------------------------------------------------------

    public static function invalidateCache(?string $id = null): void
    {
        if ($id !== null) {
            self::store()->flush($id);
            return;
        }

        foreach (array_keys(self::store()->all()) as $panelId) {
            self::store()->flush($panelId);
        }
    }

    // -------------------------------------------------------------------------
    // Default-flag enforcement
    // -------------------------------------------------------------------------

    public static function setDefault(string $id): void
    {
        foreach (self::all() as $panelId => $dto) {
            self::store()->patch($panelId, ['is_default' => (int) ($panelId === $id)]);
        }
    }

    // -------------------------------------------------------------------------

    private static function store(): PanelConfigStore
    {
        return app(PanelConfigStore::class);
    }
}
