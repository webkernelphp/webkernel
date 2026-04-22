<?php declare(strict_types=1);

namespace Webkernel\Integration;

use Illuminate\Support\Str;
use Webkernel\CryptData;

final class RegistryAccounts
{
    private const TABLE = 'registry_accounts';

    public static function register(
        Registries $registry,
        string     $accountName,
        string     $token,
        string     $type = 'personal',
        ?string    $email = null,
        ?array     $metadata = null,
    ): string {
        $id = Str::ulid();
        $tokenEncrypted = CryptData::encrypt($token);
        $metadataEncrypted = $metadata ? CryptData::encrypt(json_encode($metadata)) : null;

        \DB::connection('webkernel_sqlite')
            ->table(self::TABLE)
            ->updateOrInsert(
                ['registry' => $registry->value, 'account_name' => $accountName],
                [
                    'id' => $id,
                    'account_type' => $type,
                    'account_email' => $email,
                    'token_encrypted' => $tokenEncrypted,
                    'metadata_encrypted' => $metadataEncrypted,
                    'active' => true,
                    'updated_at' => now(),
                ],
            );

        return $id;
    }

    public static function retrieve(Registries $registry, string $accountName): ?array
    {
        $row = \DB::connection('webkernel_sqlite')
            ->table(self::TABLE)
            ->where('registry', $registry->value)
            ->where('account_name', $accountName)
            ->where('active', true)
            ->first([
                'id', 'account_name', 'account_email', 'account_type',
                'token_encrypted', 'metadata_encrypted', 'verified',
            ]);

        if (!$row) {
            return null;
        }

        $metadata = $row->metadata_encrypted
            ? json_decode(CryptData::decrypt($row->metadata_encrypted) ?? '{}', true)
            : [];

        return [
            'id' => $row->id,
            'name' => $row->account_name,
            'email' => $row->account_email,
            'type' => $row->account_type,
            'token' => CryptData::decrypt($row->token_encrypted),
            'metadata' => $metadata,
            'verified' => $row->verified,
        ];
    }

    public static function listByRegistry(Registries $registry): array
    {
        $rows = \DB::connection('webkernel_sqlite')
            ->table(self::TABLE)
            ->where('registry', $registry->value)
            ->where('active', true)
            ->orderBy('account_name')
            ->get(['id', 'account_name', 'account_email', 'account_type', 'verified', 'created_at']);

        return $rows->map(fn ($row) => [
            'id' => $row->id,
            'name' => $row->account_name,
            'email' => $row->account_email,
            'type' => $row->account_type,
            'verified' => $row->verified,
            'created_at' => $row->created_at,
        ])->toArray();
    }

    public static function verify(string $accountId): void
    {
        \DB::connection('webkernel_sqlite')
            ->table(self::TABLE)
            ->where('id', $accountId)
            ->update(['verified' => true]);
    }

    public static function deactivate(string $accountId): void
    {
        \DB::connection('webkernel_sqlite')
            ->table(self::TABLE)
            ->where('id', $accountId)
            ->update(['active' => false]);
    }

    public static function delete(string $accountId): void
    {
        \DB::connection('webkernel_sqlite')
            ->table(self::TABLE)
            ->where('id', $accountId)
            ->delete();
    }
}
