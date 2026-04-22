<?php declare(strict_types=1);

namespace Webkernel\BackOffice\Installer\Presentation\Installer;

use Webkernel\Users\Models\UserPrivilege;

/**
 * Canonical installation-state resolver.
 *
 * Three states are distinguished:
 *
 *   NOT_INSTALLED   -- .env or deployment.php missing, or APP_KEY not set.
 *                      The installer must run from the beginning.
 *
 *   MISSING_ADMIN   -- Infrastructure is ready but no privilege record exists.
 *                      The installer must resume at the create_user phase.
 *
 *   INSTALLED       -- At least one privilege record exists. Normal boot.
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
     * True when any privilege record exists.
     *
     * Any row in user_privileges means the wizard completed — regardless of
     * which role was chosen (external-sysadmin is just as valid as app-owner).
     * Wrapped in a try/catch so a missing table on a fresh install is silent.
     */
    public static function hasPrivilegedUser(): bool
    {
        try {
            return UserPrivilege::query()->exists();
        } catch (\Throwable) {
            return false;
        }
    }
}
