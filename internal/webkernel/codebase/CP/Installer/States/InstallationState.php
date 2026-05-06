<?php declare(strict_types=1);

namespace Webkernel\CP\Installer\States;

use BackedEnum;
use RuntimeException;
use Webkernel\Base\Users\Models\UserPrivilege;

/**
 * Installation lifecycle phase enumeration.
 *
 * Defines the complete workflow from initial pre-flight checks
 * through final setup completion.
 */
enum InstallationPhase: string
{
    case PRE = 'pre';
    case INSTALLING = 'installing';
    case VERIFY_TOKEN = 'verify_token';
    case SETUP = 'setup';
    case ERROR = 'error';

    public function label(): string
    {
        return match ($this) {
            self::PRE => 'Pre-flight checks — requirements and capabilities',
            self::INSTALLING => 'Installation in progress...',
            self::VERIFY_TOKEN => 'Enter your one-time Setup Token to continue',
            self::SETUP => 'Complete the setup wizard',
            self::ERROR => 'Installation encountered an error',
        };
    }
}

/**
 * Application installation state resolver.
 *
 * Three distinct states are recognized:
 *
 *   NOT_INSTALLED  :: .env or deployment.php missing, or APP_KEY not set.
 *   MISSING_ADMIN  :: Infrastructure is ready but no privilege record exists.
 *   INSTALLED      :: At least one privilege record exists.
 */
final class InstallationState
{
    /**
     * Resolve the current installation state.
     */
    public static function resolve(): string
    {
        if (!static::infrastructureReady()) {
            return InstallationConstants::STATE_NOT_INSTALLED;
        }

        if (!static::hasPrivilegedUser()) {
            return InstallationConstants::STATE_MISSING_ADMIN;
        }

        return InstallationConstants::STATE_INSTALLED;
    }

    /**
     * Determine the next phase based on infrastructure and token config.
     */
    public static function nextPhase(): InstallationPhase
    {
        if (!static::infrastructureReady()) {
            return InstallationPhase::PRE;
        }

        return static::getConfiguredToken() !== null
            ? InstallationPhase::VERIFY_TOKEN
            : InstallationPhase::SETUP;
    }

    /**
     * Check if the .env file, APP_KEY, and deployment.php are in place.
     */
    public static function infrastructureReady(): bool
    {
        if (!is_file(ENV_PATH)) {
            return false;
        }

        $key = trim((string) config('app.key', ''));
        if ($key === '' || !str_starts_with($key, 'base64:') || strlen($key) <= 30) {
            return false;
        }

        return is_file(WEBKERNEL_INSTANCE_FILE);
    }

    /**
     * Check if any privilege record exists in the database.
     */
    public static function hasPrivilegedUser(): bool
    {
        try {
            return UserPrivilege::query()->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Resolve or generate the setup token from file or environment.
     *
     * Resolution order:
     *   1. Environment variable (WEBKERNEL_SETUP_TOKEN)
     *   2. Config value (webkernel.setup_token)
     *   3. Token file (PLATFORM_DIR/.setup_token)
     *   4. Generate new token and write file
     *
     * @throws RuntimeException
     */
    public static function resolveToken(): string
    {
        $configuredToken = static::getConfiguredToken();

        if ($configuredToken !== null) {
            return $configuredToken;
        }

        $tokenFile = InstallationConstants::TOKEN_FILE_PATH;

        if (!file_exists($tokenFile)) {
            static::createTokenFile($tokenFile);
        }

        $content = @file_get_contents($tokenFile);

        if ($content === false) {
            throw new RuntimeException(
                "Cannot read setup token from: {$tokenFile}"
            );
        }

        return trim($content);
    }

    /**
     * Invalidate the setup token by deleting the token file.
     */
    public static function invalidateToken(): void
    {
        $tokenFile = InstallationConstants::TOKEN_FILE_PATH;

        if (file_exists($tokenFile)) {
            @unlink($tokenFile);
        }
    }

    /**
     * Get token from environment or config (if configured).
     */
    private static function getConfiguredToken(): ?string
    {
        $envToken = env(InstallationConstants::TOKEN_ENV_KEY);
        if ($envToken !== null) {
            return $envToken;
        }

        $configToken = config(InstallationConstants::TOKEN_CONFIG_KEY);
        if ($configToken !== null) {
            return (string) $configToken;
        }

        return null;
    }

    /**
     * Create and write the setup token file.
     *
     * @throws RuntimeException
     */
    private static function createTokenFile(string $tokenFile): void
    {
        $tokenDir = InstallationConstants::TOKEN_DIR_PATH;

        if (!is_dir($tokenDir)) {
            if (!@mkdir($tokenDir, InstallationConstants::TOKEN_DIR_PERMISSIONS, true)) {
                throw new RuntimeException(
                    "Cannot create token directory: {$tokenDir}"
                );
            }
        }

        $token = \Illuminate\Support\Str::random(InstallationConstants::TOKEN_LENGTH);

        if (!@file_put_contents($tokenFile, $token, LOCK_EX)) {
            throw new RuntimeException(
                "Cannot write token file: {$tokenFile}"
            );
        }

        @chmod($tokenFile, InstallationConstants::TOKEN_FILE_PERMISSIONS);
    }
}
