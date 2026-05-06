<?php declare(strict_types=1);

namespace Webkernel\CP\Installer\States;


/**
 * Installation constants.
 *
 * Centralizes all typed constants for the installation process:
 * paths, token configuration, state codes, environment variables.
 */
final class InstallationConstants
{
    // =========================================================================
    // STATE CODES
    // =========================================================================

    public const string STATE_NOT_INSTALLED = 'not_installed';
    public const string STATE_MISSING_ADMIN = 'missing_admin';
    public const string STATE_INSTALLED = 'installed';

    // =========================================================================
    // ROUTES & PATHS
    // =========================================================================

    public const string ROUTE_PREFIX = 'installer';
    public const string ROUTE_URL = '/installer';
    public const string HEALTH_PATH = '/up';

    // =========================================================================
    // TOKEN CONFIGURATION
    // =========================================================================

    public const string TOKEN_FILE_PATH = PLATFORM_DIR . '/.setup_token';
    public const string TOKEN_DIR_PATH = PLATFORM_DIR;
    public const int TOKEN_LENGTH = 48;
    public const int TOKEN_FILE_PERMISSIONS = 0o600;
    public const int TOKEN_DIR_PERMISSIONS = 0o700;

    // =========================================================================
    // ENVIRONMENT & CONFIG KEYS
    // =========================================================================

    public const string TOKEN_ENV_KEY = 'WEBKERNEL_SETUP_TOKEN';
    public const string TOKEN_CONFIG_KEY = 'webkernel.setup_token';
}
