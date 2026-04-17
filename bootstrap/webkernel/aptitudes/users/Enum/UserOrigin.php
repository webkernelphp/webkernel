<?php declare(strict_types=1);

namespace Webkernel\Users\Enum;

/**
 * Discriminator that describes where a user originates.
 *
 * Stored in user_privileges.user_origin as a plain string so that the column
 * remains portable across SQLite, MySQL and PostgreSQL without requiring a
 * native ENUM type.
 *
 * Invariants enforced at the application layer:
 *   - APP_OWNER is only valid when origin = INTERNAL.
 *   - EXTERNAL_SUPER_USER / EXTERNAL_MEMBER require origin = EXTERNAL.
 *   - SUPER_USER and MEMBER may only be used with INTERNAL.
 */
enum UserOrigin: string
{
    /** Regular organisation member managed directly by Webkernel. */
    case INTERNAL = 'internal';

    /** Contractor, auditor, or external service provider. */
    case EXTERNAL = 'external';

    // ── Labels ───────────────────────────────────────────────────────────────

    public function label(): string
    {
        return match ($this) {
            self::INTERNAL => 'Internal',
            self::EXTERNAL => 'External',
        };
    }

    // ── Validation helpers ────────────────────────────────────────────────────

    /**
     * Asserts that the given privilege level is compatible with this origin.
     *
     * @throws \LogicException when the combination is invalid.
     */
    public function assertCompatible(UserPrivilegeLevel $level): void
    {
        $valid = match ($this) {
            self::INTERNAL => $level->isInternalOnly(),
            self::EXTERNAL => $level->isExternalLevel(),
        };

        if (! $valid) {
            throw new \LogicException(sprintf(
                'Privilege level "%s" is not compatible with origin "%s".',
                $level->value,
                $this->value,
            ));
        }
    }

    /**
     * Returns true when $level is valid for this origin, false otherwise.
     */
    public function isCompatibleWith(UserPrivilegeLevel $level): bool
    {
        return match ($this) {
            self::INTERNAL => $level->isInternalOnly(),
            self::EXTERNAL => $level->isExternalLevel(),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::INTERNAL->value => self::INTERNAL->label(),
            self::EXTERNAL->value => self::EXTERNAL->label(),
        ];
    }

    public static function fromKey(string $key): ?self
    {
        return self::tryFrom($key);
    }
}
