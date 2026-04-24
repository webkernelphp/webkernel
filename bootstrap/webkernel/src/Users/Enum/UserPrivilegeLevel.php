<?php declare(strict_types=1);

namespace Webkernel\Users\Enum;

/**
 * ════════════════════════════════════════════════════════════════════════════
 * UserPrivilegeLevel
 * ════════════════════════════════════════════════════════════════════════════
 *
 * Defines the privilege levels of users WITHIN THE WEBKERNEL PLATFORM INSTANCE.
 *
 * ┌─────────────────────────────────────────────────────────────────────────┐
 * │  SCOPE OF THIS ENUM                                                     │
 * │                                                                         │
 * │  This enum governs platform-level access only:                          │
 * │  → Who can manage the platform itself (settings, modules, billing…)     │
 * │  → Who can configure or administer the instance                         │
 * │                                                                         │
 * │  This enum does NOT govern:                                             │
 * │  → End-users inside modules (hotels, banks, SaaS apps…)                │
 * │  → Module-level customers, sub-customers, or their admins               │
 * │  → Fine-grained permissions within a module                             │
 * │                                                                         │
 * │  Module-level access is handled by a dedicated Role/Permission system   │
 * │  (RBAC) that each module can extend independently.                      │
 * └─────────────────────────────────────────────────────────────────────────┘
 *
 * ── Privilege Levels ────────────────────────────────────────────────────────
 *
 * Each level exists in TWO variants: INTERNAL and EXTERNAL.
 * Internal = direct organisation member managed by Webkernel.
 * External = contractor, auditor, integrator, service provider…
 *
 * IMPORTANT — Rank vs Origin:
 *   • The RANK (hierarchy weight) is the same for internal and external
 *     variants at equivalent levels. A SUPER_ADMIN is a SUPER_ADMIN.
 *   • The ORIGIN (internal/external) is a separate attribute (@see UserOrigin)
 *     used by the app owner to control visibility, feature access, or UI.
 *   • APP_OWNER is strictly reserved for INTERNAL users. There is no
 *     external equivalent of the application owner.
 *
 * ── Hierarchy (rank) ────────────────────────────────────────────────────────
 *
 *   APP_OWNER      (60)  — internal only
 *   SUPER_ADMIN    (50)  — internal & external share this rank
 *   SYSADMIN       (40)  — internal & external share this rank
 *   STAFF          (30)  — internal & external share this rank
 *
 * ── Naming clarification ────────────────────────────────────────────────────
 *
 *   STAFF replaces the former "MEMBER" to avoid ambiguity.
 *   "Member" is reserved for module-level end-user concepts.
 *   STAFF = a platform-level team member (internal or external collaborator).
 *
 * @see UserOrigin  for the internal/external discriminator.
 */
enum UserPrivilegeLevel: string
{
    // ── Internal ──────────────────────────────────────────────────────────────

    /** Full platform ownership. One per instance. Internal only. */
    case APP_OWNER  = 'app-owner';

    /** Full administrative access, internal team. */
    case SUPER_ADMIN = 'super-admin';

    /** Technical/infrastructure administrator, internal. */
    case SYSADMIN   = 'sysadmin';

    /** Standard platform team member, internal. */
    case STAFF      = 'staff';

    // ── External (contractors, auditors, integrators, service providers…) ─────

    /** Full administrative access, externally contracted. Same rank as SUPER_ADMIN. */
    case EXTERNAL_SUPER_ADMIN = 'external-super-admin';

    /** Technical administrator, externally contracted. Same rank as SYSADMIN. */
    case EXTERNAL_SYSADMIN    = 'external-sysadmin';

    /** Standard collaborator, externally contracted. Same rank as STAFF. */
    case EXTERNAL_STAFF       = 'external-staff';


    // ════════════════════════════════════════════════════════════════════════
    // Labels & descriptions
    // ════════════════════════════════════════════════════════════════════════

    public function label(): string
    {
        return match ($this) {
            self::APP_OWNER           => 'Application Owner',
            self::SUPER_ADMIN         => 'Super Administrator',
            self::SYSADMIN            => 'System Administrator',
            self::STAFF               => 'Staff Member',
            self::EXTERNAL_SUPER_ADMIN => 'External Super Administrator',
            self::EXTERNAL_SYSADMIN   => 'External System Administrator',
            self::EXTERNAL_STAFF      => 'External Collaborator',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::APP_OWNER =>
                'Ultimate authority over the platform instance. ' .
                'Reserved for internal users only. Cannot be assigned externally.',
            self::SUPER_ADMIN =>
                'Full administrative access to all platform features and modules (internal).',
            self::SYSADMIN =>
                'Technical control over infrastructure, integrations and system configuration (internal).',
            self::STAFF =>
                'Standard authenticated platform team member with role-assigned permissions (internal).',
            self::EXTERNAL_SUPER_ADMIN =>
                'Full administrative access, equivalent to Super Admin, granted to an external contractor or partner.',
            self::EXTERNAL_SYSADMIN =>
                'Technical system access equivalent to SysAdmin, granted to an external provider or auditor.',
            self::EXTERNAL_STAFF =>
                'Standard collaborator access equivalent to Staff, granted to an external contractor.',
        };
    }


    // ════════════════════════════════════════════════════════════════════════
    // Rank & Hierarchy
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Numeric rank. Higher = more privileged.
     *
     * Internal and external equivalents share the same rank intentionally:
     * privilege level is orthogonal to origin.
     *
     *   APP_OWNER              60   (no external equivalent)
     *   SUPER_ADMIN            50   ↔  EXTERNAL_SUPER_ADMIN  50
     *   SYSADMIN               40   ↔  EXTERNAL_SYSADMIN     40
     *   STAFF                  30   ↔  EXTERNAL_STAFF        30
     */
    public function rank(): int
    {
        return match ($this) {
            self::APP_OWNER                                => 60,
            self::SUPER_ADMIN, self::EXTERNAL_SUPER_ADMIN  => 50,
            self::SYSADMIN,    self::EXTERNAL_SYSADMIN     => 40,
            self::STAFF,       self::EXTERNAL_STAFF        => 30,
        };
    }

    /** True when $this ranks strictly above $other (regardless of origin). */
    public function isAbove(self $other): bool
    {
        return $this->rank() > $other->rank();
    }

    /** True when $this ranks at least as high as $other. */
    public function isAtLeast(self $other): bool
    {
        return $this->rank() >= $other->rank();
    }

    /** True when $this ranks strictly below $other. */
    public function isBelow(self $other): bool
    {
        return $this->rank() < $other->rank();
    }

    /** True when both levels share the same rank (equivalent privilege-wise). */
    public function isEquivalentTo(self $other): bool
    {
        return $this->rank() === $other->rank();
    }


    // ════════════════════════════════════════════════════════════════════════
    // Origin helpers
    // ════════════════════════════════════════════════════════════════════════

    public function origin(): UserOrigin
    {
        return match ($this) {
            self::APP_OWNER,
            self::SUPER_ADMIN,
            self::SYSADMIN,
            self::STAFF              => UserOrigin::INTERNAL,
            self::EXTERNAL_SUPER_ADMIN,
            self::EXTERNAL_SYSADMIN,
            self::EXTERNAL_STAFF     => UserOrigin::EXTERNAL,
        };
    }

    public function isInternal(): bool
    {
        return $this->origin() === UserOrigin::INTERNAL;
    }

    public function isExternal(): bool
    {
        return $this->origin() === UserOrigin::EXTERNAL;
    }

    /** @deprecated Use isInternal() instead. */
    public function isInternalOnly(): bool
    {
        return $this->isInternal();
    }

    /** @deprecated Use isExternal() instead. */
    public function isExternalLevel(): bool
    {
        return $this->isExternal();
    }


    // ════════════════════════════════════════════════════════════════════════
    // Equivalence & scope queries
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Returns all privilege levels that are equivalent in rank to $this,
     * optionally filtered by origin scope.
     *
     * @param  'internal'|'external'|'both'  $scope
     * @return list<self>
     *
     * Examples:
     *   SUPER_ADMIN->orEquivalent('both')      → [SUPER_ADMIN, EXTERNAL_SUPER_ADMIN]
     *   EXTERNAL_SYSADMIN->orEquivalent('internal') → [SYSADMIN]
     *   STAFF->orEquivalent('external')        → [EXTERNAL_STAFF]
     */
    public function orEquivalent(string $scope = 'both'): array
    {
        return $this->filterByRankAndScope($this->rank(), $scope, strict: false);
    }

    /**
     * Returns all levels that rank at or above $this,
     * optionally filtered by origin scope.
     *
     * @param  'internal'|'external'|'both'  $scope
     * @return list<self>
     */
    public function atOrAbove(string $scope = 'both'): array
    {
        $rank = $this->rank();

        return array_values(array_filter(
            self::allByRank(),
            fn (self $c) => $c->rank() >= $rank && $this->matchesScope($c, $scope),
        ));
    }

    /**
     * Returns all levels that rank strictly below $this,
     * optionally filtered by origin scope.
     *
     * @param  'internal'|'external'|'both'  $scope
     * @return list<self>
     */
    public function below(string $scope = 'both'): array
    {
        $rank = $this->rank();

        return array_values(array_filter(
            self::allByRank(),
            fn (self $c) => $c->rank() < $rank && $this->matchesScope($c, $scope),
        ));
    }


    // ════════════════════════════════════════════════════════════════════════
    // Collections & option lists
    // ════════════════════════════════════════════════════════════════════════

    /** @return list<self> All internal levels, ordered high → low. */
    public static function internalLevels(): array
    {
        return [self::APP_OWNER, self::SUPER_ADMIN, self::SYSADMIN, self::STAFF];
    }

    /** @return list<self> All external levels, ordered high → low. */
    public static function externalLevels(): array
    {
        return [self::EXTERNAL_SUPER_ADMIN, self::EXTERNAL_SYSADMIN, self::EXTERNAL_STAFF];
    }

    /** @return list<self> All levels ordered by rank descending. */
    public static function allByRank(): array
    {
        $cases = self::cases();
        usort($cases, fn (self $a, self $b) => $b->rank() <=> $a->rank());
        return $cases;
    }

    /** @return array<string, string> value → label map for all levels. */
    public static function options(): array
    {
        return array_column(
            array_map(fn (self $c) => [$c->value, $c->label()], self::allByRank()),
            1, 0,
        );
    }

    /** @return array<string, string> */
    public static function internalOptions(): array
    {
        return array_column(
            array_map(fn (self $c) => [$c->value, $c->label()], self::internalLevels()),
            1, 0,
        );
    }

    /** @return array<string, string> */
    public static function externalOptions(): array
    {
        return array_column(
            array_map(fn (self $c) => [$c->value, $c->label()], self::externalLevels()),
            1, 0,
        );
    }

    /** Returns null if the key does not match any case (no exception). */
    public static function fromKey(string $key): ?self
    {
        return self::tryFrom($key);
    }


    // ════════════════════════════════════════════════════════════════════════
    // Private helpers
    // ════════════════════════════════════════════════════════════════════════

    private function filterByRankAndScope(int $rank, string $scope, bool $strict): array
    {
        return array_values(array_filter(
            self::allByRank(),
            fn (self $c) => $c->rank() === $rank && $this->matchesScope($c, $scope),
        ));
    }

    /**
     * @param  'internal'|'external'|'both'  $scope
     * @throws \InvalidArgumentException on unknown scope value.
     */
    private function matchesScope(self $case, string $scope): bool
    {
        return match ($scope) {
            'internal' => $case->isInternal(),
            'external' => $case->isExternal(),
            'both'     => true,
            default    => throw new \InvalidArgumentException(
                "Invalid scope \"{$scope}\". Expected: 'internal', 'external', or 'both'.",
            ),
        };
    }
}
