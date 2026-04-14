<?php declare(strict_types=1);

namespace Webkernel\Users\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkernel\Users\Enum\UserPrivilegeLevel;

/**
 * @property int               $id
 * @property int               $user_id
 * @property UserPrivilegeLevel $privilege
 * @property-read User          $user
 * @method static Builder|static forPrivilege(UserPrivilegeLevel|string $privilege)
 * @method static Builder|static appOwners()
 * @method static Builder|static superUsers()
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static Builder<static>|UserPrivilege newModelQuery()
 * @method static Builder<static>|UserPrivilege newQuery()
 * @method static Builder<static>|UserPrivilege query()
 * @method static Builder<static>|UserPrivilege whereCreatedAt($value)
 * @method static Builder<static>|UserPrivilege whereId($value)
 * @method static Builder<static>|UserPrivilege wherePrivilege($value)
 * @method static Builder<static>|UserPrivilege whereUpdatedAt($value)
 * @method static Builder<static>|UserPrivilege whereUserId($value)
 * @mixin \Eloquent
 * @mixin IdeHelperUserPrivilege
 */
class UserPrivilege extends Model
{
    protected $table    = 'user_privileges';
    protected $fillable = ['user_id', 'privilege'];
    protected $casts    = ['privilege' => UserPrivilegeLevel::class];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeForPrivilege(Builder $query, UserPrivilegeLevel|string $privilege): Builder
    {
        $value = $privilege instanceof UserPrivilegeLevel ? $privilege->value : $privilege;

        return $query->where('privilege', $value);
    }

    public function scopeAppOwners(Builder $query): Builder
    {
        return $query->where('privilege', UserPrivilegeLevel::APP_OWNER->value);
    }

    public function scopeSuperUsers(Builder $query): Builder
    {
        return $query->where('privilege', UserPrivilegeLevel::SUPER_USER->value);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isAppOwner(): bool
    {
        return $this->privilege === UserPrivilegeLevel::APP_OWNER;
    }

    public function isSuperUserOrAbove(): bool
    {
        return $this->privilege === UserPrivilegeLevel::SUPER_USER
            || $this->privilege->isAbove(UserPrivilegeLevel::MEMBER);
    }
}
