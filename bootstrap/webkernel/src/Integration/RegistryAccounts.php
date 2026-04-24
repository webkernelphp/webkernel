<?php declare(strict_types=1);

namespace Webkernel\Integration;

use Illuminate\Support\Str;
use Webkernel\CryptData;
use Webkernel\Integration\Models\RegistryAccount;

final class RegistryAccounts
{
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

        RegistryAccount::updateOrCreate(
            ['registry' => $registry->value, 'account_name' => $accountName],
            [
                'id' => $id,
                'account_type' => $type,
                'account_email' => $email,
                'token_encrypted' => $tokenEncrypted,
                'metadata_encrypted' => $metadataEncrypted,
                'active' => true,
            ],
        );

        return $id;
    }

    public static function retrieve(Registries $registry, string $accountName): ?array
    {
        $account = RegistryAccount::where('registry', $registry->value)
            ->where('account_name', $accountName)
            ->where('active', true)
            ->first();

        if (!$account) {
            return null;
        }

        $metadata = $account->metadata_encrypted
            ? json_decode(CryptData::decrypt($account->metadata_encrypted) ?? '{}', true)
            : [];

        return [
            'id' => $account->id,
            'name' => $account->account_name,
            'email' => $account->account_email,
            'type' => $account->account_type,
            'token' => CryptData::decrypt($account->token_encrypted),
            'metadata' => $metadata,
            'verified' => $account->verified,
        ];
    }

    public static function listByRegistry(Registries $registry): array
    {
        return RegistryAccount::where('registry', $registry->value)
            ->where('active', true)
            ->orderBy('account_name')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->account_name,
                'email' => $row->account_email,
                'type' => $row->account_type,
                'verified' => $row->verified,
                'created_at' => $row->created_at,
            ])
            ->toArray();
    }

    public static function verify(string $accountId): void
    {
        RegistryAccount::where('id', $accountId)->update(['verified' => true]);
    }

    public static function deactivate(string $accountId): void
    {
        RegistryAccount::where('id', $accountId)->update(['active' => false]);
    }

    public static function delete(string $accountId): void
    {
        RegistryAccount::destroy($accountId);
    }
}
