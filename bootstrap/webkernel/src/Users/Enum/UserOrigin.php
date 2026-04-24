<?php declare(strict_types=1);

namespace Webkernel\Users\Enum;

/**
 * ════════════════════════════════════════════════════════════════════════════
 * UserOrigin
 * ════════════════════════════════════════════════════════════════════════════
 *
 * Discriminator describing where a platform user originates.
 *
 * This is ORTHOGONAL to UserPrivilegeLevel:
 *   → Origin    = WHO the person is (inside the org vs external collaborator)
 *   → Privilege = WHAT they can do (their rank in the hierarchy)
 *
 * The combination (origin + privilege) lets the app owner make decisions like:
 *   "Show module X to internal super-admins but NOT to external super-admins."
 *   "Allow sysadmin actions to internal sysadmins only."
 *
 * Stored as a plain string for DB portability (SQLite / MySQL / PostgreSQL).
 *
 * ── Invariants (enforced at application layer) ───────────────────────────────
 *   • APP_OWNER → INTERNAL only. No external equivalent exists.
 *   • All other privilege levels → available to both INTERNAL and EXTERNAL,
 *     but must use the correct variant (SUPER_ADMIN vs EXTERNAL_SUPER_ADMIN…).
 *
 * @see UserPrivilegeLevel  for the hierarchy/rank enum.
 */
enum UserOrigin: string
{
    /** Regular organisation member, managed directly by Webkernel. */
    case INTERNAL = 'internal';

    /** Contractor, auditor, integrator, or external service provider. */
    case EXTERNAL = 'external';


    // ════════════════════════════════════════════════════════════════════════
    // Labels
    // ════════════════════════════════════════════════════════════════════════

    public function label(): string
    {
        return match ($this) {
            self::INTERNAL => 'Internal',
            self::EXTERNAL => 'External',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::INTERNAL => 'Direct organisation member managed by the platform.',
            self::EXTERNAL => 'External contractor, auditor, integrator, or service provider.',
        };
    }


    // ════════════════════════════════════════════════════════════════════════
    // Compatibility with UserPrivilegeLevel
    // ════════════════════════════════════════════════════════════════════════

    /**
     * Asserts that this origin is consistent with the given privilege level.
     *
     * APP_OWNER is internal-only. All other levels are valid for both origins
     * but must use the matching variant (e.g. SUPER_ADMIN for INTERNAL,
     * EXTERNAL_SUPER_ADMIN for EXTERNAL).
     *
     * @throws \LogicException on incompatible combination.
     */
    public function assertCompatible(UserPrivilegeLevel $level): void
    {
        if (! $this->isCompatibleWith($level)) {
            throw new \LogicException(sprintf(
                'Privilege level "%s" is not compatible with origin "%s". ' .
                'APP_OWNER is reserved for internal users only.',
                $level->value,
                $this->value,
            ));
        }
    }

    public function isCompatibleWith(UserPrivilegeLevel $level): bool
    {
        if ($level === UserPrivilegeLevel::APP_OWNER) {
            return $this === self::INTERNAL;
        }

        return $level->origin() === $this;
    }

    /**
     * Returns all privilege levels compatible with this origin, rank descending.
     *
     * @return list<UserPrivilegeLevel>
     */
    public function compatibleLevels(): array
    {
        return array_values(array_filter(
            UserPrivilegeLevel::allByRank(),
            fn (UserPrivilegeLevel $level) => $this->isCompatibleWith($level),
        ));
    }


    // ════════════════════════════════════════════════════════════════════════
    // Utilities
    // ════════════════════════════════════════════════════════════════════════

    /** @return array<string, string> value → label map for form selects. */
    public static function options(): array
    {
        return [
            self::INTERNAL->value => self::INTERNAL->label(),
            self::EXTERNAL->value => self::EXTERNAL->label(),
        ];
    }

    /** Returns null if the key does not match any case (no exception). */
    public static function fromKey(string $key): ?self
    {
        return self::tryFrom($key);
    }
}
