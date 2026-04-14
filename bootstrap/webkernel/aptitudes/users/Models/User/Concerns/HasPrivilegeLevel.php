<?php declare(strict_types=1);

namespace Webkernel\Users\Models\User\Concerns;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Webkernel\Users\Models\UserPrivilege;
use Webkernel\Users\Enum\UserPrivilegeLevel;

/**
 * @property-read UserPrivilege|null $privilegeRelation
 */
trait HasPrivilegeLevel
{
    public function privilegeRelation(): HasOne
    {
        return $this->hasOne(UserPrivilege::class, 'user_id');
    }

    public function getPrivilegeLevel(): ?UserPrivilegeLevel
    {
        return $this->privilegeRelation?->privilege;
    }

    public function isAppOwner(): bool
    {
        return $this->getPrivilegeLevel() === UserPrivilegeLevel::APP_OWNER;
    }

    public function isSuperUser(): bool
    {
        $level = $this->getPrivilegeLevel();

        return $level === UserPrivilegeLevel::SUPER_USER
            || $level?->isAbove(UserPrivilegeLevel::MEMBER) === true;
    }

    public function hasPrivilege(UserPrivilegeLevel $level): bool
    {
        return $this->getPrivilegeLevel() === $level;
    }

    public function hasPrivilegeOrAbove(UserPrivilegeLevel $level): bool
    {
        $current = $this->getPrivilegeLevel();

        if ($current === null) {
            return false;
        }

        return $current === $level || $current->isAbove($level);
    }
}
