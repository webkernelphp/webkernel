<?php declare(strict_types=1);

namespace Webkernel\Integration;

use Illuminate\Support\Str;
use Webkernel\CryptData;
use Webkernel\Integration\Models\RegistryKey;

final class RegistryCredentials
{
    public static function store(Registries $registry, string $token, ?string $vendor = null): void
    {
        $encrypted = CryptData::encrypt($token);

        RegistryKey::updateOrCreate(
            ['registry' => $registry->value, 'vendor' => $vendor],
            [
                'id' => Str::ulid(),
                'token_encrypted' => $encrypted,
                'active' => true,
            ],
        );
    }

    public static function retrieve(Registries $registry, ?string $vendor = null): ?string
    {
        $key = RegistryKey::where('registry', $registry->value)
            ->where('vendor', $vendor)
            ->where('active', true)
            ->first();

        if (!$key) {
            return null;
        }

        return CryptData::decrypt($key->token_encrypted);
    }

    public static function list(Registries $registry): array
    {
        return RegistryKey::where('registry', $registry->value)
            ->where('active', true)
            ->get(['vendor', 'created_at'])
            ->map(fn ($row) => [
                'vendor' => $row->vendor,
                'created_at' => $row->created_at,
            ])
            ->toArray();
    }

    public static function delete(Registries $registry, ?string $vendor = null): void
    {
        RegistryKey::where('registry', $registry->value)
            ->where('vendor', $vendor)
            ->delete();
    }
}
