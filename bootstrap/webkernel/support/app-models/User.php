<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Webkernel\Users\Models\UserPrivilege;
use Illuminate\Database\Eloquent\Relations\HasOne;
/**
 * @mixin IdeHelperUser
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function privilege(): HasOne
    {
        return $this->hasOne(UserPrivilege::class);
    }

    public function isAppOwner(): bool
    {
        return $this->privilege?->privilege === 'app-owner';
    }

    public function isSuperUser(): bool
    {
        return in_array($this->privilege?->privilege, ['app-owner', 'super-user'], true);
    }
}
