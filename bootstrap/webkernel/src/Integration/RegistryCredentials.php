<?php declare(strict_types=1);

namespace Webkernel\Integration;

use Illuminate\Support\Str;
use Webkernel\CryptData;

final class RegistryCredentials
{
    private const TABLE = 'modules_src_keys';

    public static function store(Registries $registry, string $token, ?string $vendor = null): void
    {
        $encrypted = CryptData::encrypt($token);

        \DB::connection('webkernel_sqlite')
            ->table(self::TABLE)
            ->updateOrInsert(
                ['registry' => $registry->value, 'vendor' => $vendor],
                [
                    'id' => Str::ulid(),
                    'token_encrypted' => $encrypted,
                    'active' => true,
                    'updated_at' => now(),
                ],
            );
    }

    public static function retrieve(Registries $registry, ?string $vendor = null): ?string
    {
        $row = \DB::connection('webkernel_sqlite')
            ->table(self::TABLE)
            ->where('registry', $registry->value)
            ->where('vendor', $vendor)
            ->where('active', true)
            ->first(['token_encrypted']);

        if (!$row) {
            return null;
        }

        return CryptData::decrypt($row->token_encrypted);
    }

    public static function list(Registries $registry): array
    {
        $rows = \DB::connection('webkernel_sqlite')
            ->table(self::TABLE)
            ->where('registry', $registry->value)
            ->where('active', true)
            ->get(['vendor', 'created_at']);

        return $rows->map(fn ($row) => [
            'vendor' => $row->vendor,
            'created_at' => $row->created_at,
        ])->toArray();
    }

    public static function delete(Registries $registry, ?string $vendor = null): void
    {
        \DB::connection('webkernel_sqlite')
            ->table(self::TABLE)
            ->where('registry', $registry->value)
            ->where('vendor', $vendor)
            ->delete();
    }
}
