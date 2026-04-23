<?php

namespace Webkernel\Builders\DBStudio\Policies;

use Filament\Facades\Filament;
use Webkernel\Builders\DBStudio\Enums\StudioPermission;
use Webkernel\Builders\DBStudio\Models\StudioApiKey;
use Illuminate\Foundation\Auth\User;

class StudioApiKeyPolicy
{
    public function viewAny(User $user): bool
    {
        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo(StudioPermission::ManageApiKeys->value);
        }

        return true;
    }

    public function view(User $user, StudioApiKey $apiKey): bool
    {
        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo(StudioPermission::ManageApiKeys->value);
        }

        return true;
    }

    public function create(User $user): bool
    {
        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo(StudioPermission::ManageApiKeys->value);
        }

        return true;
    }

    public function update(User $user, StudioApiKey $apiKey): bool
    {
        if (! $this->belongsToCurrentTenant($apiKey)) {
            return false;
        }

        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo(StudioPermission::ManageApiKeys->value);
        }

        return true;
    }

    public function delete(User $user, StudioApiKey $apiKey): bool
    {
        if (! $this->belongsToCurrentTenant($apiKey)) {
            return false;
        }

        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo(StudioPermission::ManageApiKeys->value);
        }

        return true;
    }

    protected function belongsToCurrentTenant(StudioApiKey $apiKey): bool
    {
        $tenantId = Filament::getTenant()?->getKey();

        if ($tenantId === null) {
            return true;
        }

        return $apiKey->tenant_id === $tenantId;
    }
}
