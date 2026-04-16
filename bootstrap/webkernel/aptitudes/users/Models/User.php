<?php declare(strict_types=1);

namespace Webkernel\Users\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Webkernel\Traits\HasQuickTouch;
use Webkernel\Users\Models\User\Concerns\HasPrivilegeLevel;

/**
 * @property int         $id
 * @property string      $name
 * @property string      $email
 * @property string|null $avatar_url
 * @property-read UserPrivilege|null $privilegeRelation
 * @method static \Illuminate\Database\Eloquent\Builder|static query()
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property string|null $app_authentication_secret
 * @property string|null $app_authentication_recovery_codes
 * @property int $has_email_authentication
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAppAuthenticationRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAppAuthenticationSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAvatarUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereHasEmailAuthentication($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @mixin \Eloquent
 * @mixin IdeHelperUser
 */
#[Table('users')]
#[Fillable(['name', 'email', 'password', 'avatar_url'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements
    FilamentUser,
    HasAvatar,
    HasName,
    MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use Notifiable;
    use HasPrivilegeLevel;
    use HasQuickTouch;

    // -------------------------------------------------------------------------
    // Casts
    // -------------------------------------------------------------------------

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // -------------------------------------------------------------------------
    // FilamentUser
    // -------------------------------------------------------------------------

    /**
     * Gate for panel access.
     * APP_OWNER and SUPER_USER always pass.
     * MEMBER must have a verified email.
     * No privilege record = deny.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasPrivilegeOrAbove(\Webkernel\Users\Enum\UserPrivilegeLevel::MEMBER);
    }

    // -------------------------------------------------------------------------
    // HasAvatar
    // -------------------------------------------------------------------------

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ?? null;
    }

    // -------------------------------------------------------------------------
    // HasName
    // -------------------------------------------------------------------------

    public function getFilamentName(): string
    {
        return $this->name;
    }
}
