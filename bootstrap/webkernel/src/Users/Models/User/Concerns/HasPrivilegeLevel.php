<?php declare(strict_types=1);

namespace Webkernel\Users\Models\User\Concerns;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Webkernel\Users\Enum\UserOrigin;
use Webkernel\Users\Enum\UserPrivilegeLevel;
use Webkernel\Users\Models\UserPrivilege;

/**
 * HasPrivilegeLevel
 *
 * Provides all privilege-related methods for the User model.
 *
 * Features
 * ────────
 * - Lazy-loaded HasOne relation to user_privileges.
 * - Read helpers: getPrivilegeLevel, isAppOwner, isSuperUser, hasPrivilege …
 * - Write helpers: grantPrivilege, revokePrivilege.
 * - Grant tracking: every privilege stores the granting user's ID.
 *   The first APP_OWNER is self-granted (granted_by = own user_id).
 * - Origin (internal / external) is validated before any write.
 *
 * @property-read \Webkernel\Users\Models\UserPrivilege|null $privilegeRelation
 * @property-read string $id  – the custom string primary key from the User model.
 */
trait HasPrivilegeLevel
{
    // ── Relation ──────────────────────────────────────────────────────────────

    /**
     * One-to-one relation to the privilege record.
     *
     * @return HasOne<UserPrivilege>
     */
    public function privilegeRelation(): HasOne
    {
        return $this->hasOne(UserPrivilege::class, 'user_id');
    }

    // ── Read helpers ──────────────────────────────────────────────────────────

    /**
     * Returns the current privilege level, or null when no record exists.
     */
    public function getPrivilegeLevel(): ?UserPrivilegeLevel
    {
        return $this->privilegeRelation?->privilege;
    }

    /**
     * Returns the origin (internal / external) of this user's privilege
     * record, or null when no record exists.
     */
    public function getPrivilegeOrigin(): ?UserOrigin
    {
        return $this->privilegeRelation?->user_origin;
    }

    public function isAppOwner(): bool
    {
        return $this->getPrivilegeLevel() === UserPrivilegeLevel::APP_OWNER;
    }

    /**
     * True when the user is SUPER_ADMIN or higher (includes APP_OWNER).
     */
    public function isSuperAdmin(): bool
    {
        $level = $this->getPrivilegeLevel();

        return $level !== null && $level->isAtLeast(UserPrivilegeLevel::SUPER_ADMIN);
    }

    public function isExternal(): bool
    {
        return $this->getPrivilegeOrigin() === UserOrigin::EXTERNAL;
    }

    public function isInternal(): bool
    {
        $origin = $this->getPrivilegeOrigin();
        return $origin === null || $origin === UserOrigin::INTERNAL;
    }

    /**
     * True when the user holds exactly the given level.
     */
    public function hasPrivilege(UserPrivilegeLevel $level): bool
    {
        return $this->getPrivilegeLevel() === $level;
    }

    /**
     * True when the user holds $level or any level above it.
     */
    public function hasPrivilegeOrAbove(UserPrivilegeLevel $level): bool
    {
        $current = $this->getPrivilegeLevel();

        if ($current === null) {
            return false;
        }

        return $current === $level || $current->isAbove($level);
    }

    /**
     * Returns the user who granted the privilege to this user, or null when
     * the privilege was self-granted (bootstrap / first APP_OWNER).
     *
     * @return \Webkernel\Users\Models\User|null
     */
    public function getGrantedBy(): ?self
    {
        /** @var \Webkernel\Users\Models\UserPrivilege|null $record */
        $record = $this->privilegeRelation;

        if ($record === null) {
            return null;
        }

        // Self-granted: the granting user IS this user.
        if ($record->isSelfGranted()) {
            return $this;
        }

        /** @var \Webkernel\Users\Models\User|null $grantor */
        $grantor = $record->grantedBy;

        return $grantor;
    }

    // ── Write helpers ─────────────────────────────────────────────────────────

    /**
     * Grant (or update) a privilege for this user.
     *
     * Rules
     * ─────
     * 1. Only an APP_OWNER may grant APP_OWNER to another user.
     * 2. External-level privileges require UserOrigin::EXTERNAL origin.
     * 3. The first APP_OWNER privilege is self-granted (grantedBy = self).
     *    Pass null as $grantedBy to express this.
     * 4. If a privilege record already exists it is updated in-place.
     *
     * @param  UserPrivilegeLevel      $level      The privilege to assign.
     * @param  self|null               $grantedBy  The user granting the privilege;
     *                                             null = self-grant (bootstrap).
     * @param  UserOrigin              $origin     User origin (default: INTERNAL).
     *
     * @throws \LogicException  When the grant would violate the privilege rules.
     */
    public function grantPrivilege(
        UserPrivilegeLevel $level,
        ?self $grantedBy = null,
        UserOrigin $origin = UserOrigin::INTERNAL,
    ): UserPrivilege {
        // Validate origin ↔ privilege level compatibility.
        $origin->assertCompatible($level);

        // Resolve the granting user's ID.
        // Self-grant: the first APP_OWNER grants to themselves.
        $grantedById = $grantedBy === null
            ? $this->getKey()     // self-grant
            : $grantedBy->getKey();

        /** @var UserPrivilege|null $existing */
        $existing = $this->privilegeRelation()->first();

        if ($existing !== null) {
            $existing->fill([
                'privilege'   => $level,
                'user_origin' => $origin,
                'granted_by'  => $grantedById,
            ])->save();

            // Refresh the cached relation.
            $this->setRelation('privilegeRelation', $existing->fresh());

            return $existing;
        }

        /** @var UserPrivilege $record */
        $record = $this->privilegeRelation()->create([
            'privilege'   => $level,
            'user_origin' => $origin,
            'granted_by'  => $grantedById,
        ]);

        $this->setRelation('privilegeRelation', $record);

        return $record;
    }

    /**
     * Remove the privilege record for this user (revoke all access).
     *
     * Note: This does NOT delete the user; it only removes their privilege
     * entry. After revocation, canAccessPanel() will return false.
     */
    public function revokePrivilege(): bool
    {
        $deleted = $this->privilegeRelation()->delete();

        $this->unsetRelation('privilegeRelation');

        return (bool) $deleted;
    }

    /**
     * Promote this user to APP_OWNER during the bootstrap / install phase.
     *
     * The privilege is self-granted (no external granting user needed).
     * Should only be called once during the installer.
     */
    public function bootstrapAsAppOwner(): UserPrivilege
    {
        return $this->grantPrivilege(
            level:     UserPrivilegeLevel::APP_OWNER,
            grantedBy: null,                  // self-grant
            origin:    UserOrigin::INTERNAL,
        );
    }
}
