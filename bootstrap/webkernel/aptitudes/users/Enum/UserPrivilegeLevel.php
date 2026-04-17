<?php declare(strict_types=1);

namespace Webkernel\Users\Enum;

/**
 * Privilege levels available in the Webkernel user system.
 *
 * Internal levels   : app-owner | super-user | member
 * External levels   : external-super-user | external-member
 *
 * Rule: APP_OWNER is strictly reserved for internal users.
 * External users may hold EXTERNAL_SUPER_USER or EXTERNAL_MEMBER.
 *
 * @see UserOrigin for the origin discriminator.
 */
enum UserPrivilegeLevel: string
{
    // ── Internal ─────────────────────────────────────────────────────────────
    case APP_OWNER           = 'app-owner';
    case SUPER_USER          = 'super-user';
    case MEMBER              = 'member';

    // ── External (contractors, auditors, service providers …) ────────────────
    case EXTERNAL_SUPER_USER = 'external-super-user';
    case EXTERNAL_MEMBER     = 'external-member';

    // ── Labels ───────────────────────────────────────────────────────────────

    public function label(): string
    {
        return match ($this) {
            self::APP_OWNER           => 'Application Owner',
            self::SUPER_USER          => 'Super Administrator',
            self::MEMBER              => 'Member',
            self::EXTERNAL_SUPER_USER => 'External Super User',
            self::EXTERNAL_MEMBER     => 'External Member',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::APP_OWNER           => 'Ultimate authority over the application. Reserved for internal users only.',
            self::SUPER_USER          => 'Full administrative access (internal).',
            self::MEMBER              => 'Standard authenticated user (internal).',
            self::EXTERNAL_SUPER_USER => 'Elevated access scoped to assigned domains/projects (external).',
            self::EXTERNAL_MEMBER     => 'Limited, read-only or task-specific access (external).',
        };
    }

    // ── Hierarchy ─────────────────────────────────────────────────────────────

    /**
     * Returns true when $this ranks strictly above $other in the privilege
     * hierarchy. External levels are always considered below internal ones.
     *
     * Ordering (high → low):
     *   APP_OWNER > SUPER_USER > MEMBER > EXTERNAL_SUPER_USER > EXTERNAL_MEMBER
     */
    public function isAbove(self $other): bool
    {
        return $this->rank() > $other->rank();
    }

    /**
     * Numeric rank used for comparisons. Higher = more privileged.
     */
    public function rank(): int
    {
        return match ($this) {
            self::APP_OWNER           => 50,
            self::SUPER_USER          => 40,
            self::MEMBER              => 30,
            self::EXTERNAL_SUPER_USER => 20,
            self::EXTERNAL_MEMBER     => 10,
        };
    }

    // ── Origin helpers ────────────────────────────────────────────────────────

    /**
     * Whether this privilege level is restricted to internal users.
     */
    public function isInternalOnly(): bool
    {
        return match ($this) {
            self::APP_OWNER, self::SUPER_USER, self::MEMBER => true,
            default                                          => false,
        };
    }

    /**
     * Whether this privilege level is designated for external users.
     */
    public function isExternalLevel(): bool
    {
        return ! $this->isInternalOnly();
    }

    // ── Collections ───────────────────────────────────────────────────────────

    /**
     * All internal privilege levels.
     *
     * @return list<self>
     */
    public static function internalLevels(): array
    {
        return [self::APP_OWNER, self::SUPER_USER, self::MEMBER];
    }

    /**
     * All external privilege levels.
     *
     * @return list<self>
     */
    public static function externalLevels(): array
    {
        return [self::EXTERNAL_SUPER_USER, self::EXTERNAL_MEMBER];
    }

    /**
     * Key → label map for all levels (e.g. for select inputs).
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }
        return $out;
    }

    /**
     * Key → label map limited to internal levels.
     *
     * @return array<string, string>
     */
    public static function internalOptions(): array
    {
        $out = [];
        foreach (self::internalLevels() as $case) {
            $out[$case->value] = $case->label();
        }
        return $out;
    }

    /**
     * Key → label map limited to external levels.
     *
     * @return array<string, string>
     */
    public static function externalOptions(): array
    {
        $out = [];
        foreach (self::externalLevels() as $case) {
            $out[$case->value] = $case->label();
        }
        return $out;
    }

    /**
     * Safe lookup from a raw string value. Returns null for unknown values.
     */
    public static function fromKey(string $key): ?self
    {
        return self::tryFrom($key);
    }
}
