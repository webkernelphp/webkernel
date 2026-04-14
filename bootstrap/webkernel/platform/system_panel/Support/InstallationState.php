<?php declare(strict_types=1);

namespace Webkernel\Platform\SystemPanel\Support;

use Webkernel\Users\Models\UserPrivilege;
use Webkernel\Users\Enum\UserPrivilegeLevel;

/**
 * Canonical installation-state resolver.
 *
 * Three states are distinguished:
 *
 *   NOT_INSTALLED   -- .env or deployment.php missing, or APP_KEY not set.
 *                      The installer must run from the beginning.
 *
 *   MISSING_ADMIN   -- Infrastructure is ready but no app-owner or super-user
 *                      row exists in user_privileges.
 *                      The installer must resume at the create_user phase.
 *
 *   INSTALLED       -- Everything is in order. Normal boot.
 *
 * Database access is attempted only when the infrastructure files are already
 * present, so this is safe to call before migrations have run on a fresh box.
 */
final class InstallationState
{
    public const NOT_INSTALLED = 'not_installed';
    public const MISSING_ADMIN = 'missing_admin';
    public const INSTALLED     = 'installed';

    /**
     * Resolve the current state.
     *
     * @return self::NOT_INSTALLED|self::MISSING_ADMIN|self::INSTALLED
     */
    public static function resolve(): string
    {
        if (! static::infrastructureReady()) {
            return self::NOT_INSTALLED;
        }

        if (! static::hasPrivilegedUser()) {
            return self::MISSING_ADMIN;
        }

        return self::INSTALLED;
    }

    // -------------------------------------------------------------------------

    /**
     * True when .env, APP_KEY, and deployment.php are all in place.
     */
    public static function infrastructureReady(): bool
    {
        if (! is_file(base_path('.env'))) {
            return false;
        }

        $key = trim((string) config('app.key', ''));

        if ($key === '' || ! str_starts_with($key, 'base64:') || strlen($key) <= 30) {
            return false;
        }

        return is_file(base_path('deployment.php'));
    }

    /**
     * True when at least one app-owner or super-user row exists.
     *
     * Wrapped in a try/catch so a missing migrations table (fresh install)
     * does not cause a fatal error.
     */
    public static function hasPrivilegedUser(): bool
    {
        try {
            return UserPrivilege::query()
                ->whereIn('privilege', [
                    UserPrivilegeLevel::APP_OWNER->value,
                    UserPrivilegeLevel::SUPER_USER->value,
                ])
                ->exists();
        } catch (\Throwable) {
            return false;
        }
    }
}
