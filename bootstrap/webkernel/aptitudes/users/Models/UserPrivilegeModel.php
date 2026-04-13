<?php declare(strict_types=1);

namespace Webkernel\Users\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkernel\Users\UserPrivilege as UserPrivilegeEnum;

/**
 * Eloquent model for the user_privileges table.
 *
 * One-to-one with User. Stores the runtime privilege level.
 * The 'privilege' column is automatically cast to the UserPrivilege enum.
 *
 * @property int                $id
 * @property int                $user_id
 * @property UserPrivilegeEnum  $privilege
 * @property \Carbon\Carbon     $created_at
 * @property \Carbon\Carbon     $updated_at
 * @property-read User          $user
 */
class UserPrivilegeModel extends Model
{
    protected $table = 'user_privileges';

    protected $fillable = [
        'user_id',
        'privilege',
    ];

    protected $casts = [
        'privilege' => UserPrivilegeEnum::class,
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    /**
     * The user this privilege belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    /**
     * Filter to a specific privilege level.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  UserPrivilegeEnum|string               $privilege
     */
    public function scopeForPrivilege($query, UserPrivilegeEnum|string $privilege): void
    {
        $value = $privilege instanceof UserPrivilegeEnum
            ? $privilege->value
            : $privilege;

        $query->where('privilege', $value);
    }

    /**
     * Retrieve all app-owner records (should be exactly one).
     */
    public function scopeAppOwners($query): void
    {
        $query->where('privilege', UserPrivilegeEnum::APP_OWNER->value);
    }

    /**
     * Retrieve all super-user records.
     */
    public function scopeSuperUsers($query): void
    {
        $query->where('privilege', UserPrivilegeEnum::SUPER_USER->value);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * True when this row grants app-owner level access.
     */
    public function isAppOwner(): bool
    {
        return $this->privilege === UserPrivilegeEnum::APP_OWNER;
    }

    /**
     * True when this row grants super-user level access (or above).
     */
    public function isSuperUserOrAbove(): bool
    {
        return in_array($this->privilege, [
            UserPrivilegeEnum::APP_OWNER,
            UserPrivilegeEnum::SUPER_USER,
        ], true);
    }
}
