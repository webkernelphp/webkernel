<?php declare(strict_types=1);

// ─────────────────────────────────────────────────────────────────────────────
// INSTALLER CONSTANTS - PHP 8.4+ with strong typing
// ─────────────────────────────────────────────────────────────────────────────

namespace Webkernel\CP\Installer\Constants;

use const PLATFORM_DIR;
use const PLATFORM_STORAGE_PATH;
use const WEBKERNEL_CACHE_PATH;


/**
 * Installer configuration with typed values
 */
final class InstallerConfig
{
    // ─────────────────────────────────────────────────────────────────
    // Paths
    // ─────────────────────────────────────────────────────────────────

    public const string TOKEN_FILE = PLATFORM_DIR . '/.setup_token';
    public const string TOKEN_DIR = PLATFORM_DIR;

    // ─────────────────────────────────────────────────────────────────
    // Token Configuration
    // ─────────────────────────────────────────────────────────────────

    public const int TOKEN_LENGTH = 48;
    public const int TOKEN_FILE_PERMISSIONS = 0o600;
    public const int TOKEN_DIR_PERMISSIONS = 0o700;

    // ─────────────────────────────────────────────────────────────────
    // Mailer Configuration
    // ─────────────────────────────────────────────────────────────────

    public const string MAILER_DEFAULT_PORT = '587';
    public const string MAILER_DEFAULT_ENCRYPTION = 'tls';

    /** @var array<string, string> */
    public const array MAILER_ENCRYPTIONS = [
        'tls' => 'TLS',
        'ssl' => 'SSL',
        'none' => 'None',
    ];

    // ─────────────────────────────────────────────────────────────────
    // Validation Constraints
    // ─────────────────────────────────────────────────────────────────

    public const int PASSWORD_MIN_LENGTH = 12;
    public const int PASSWORD_MAX_LENGTH = 255;
    public const int BUSINESS_NAME_MAX_LENGTH = 255;
    public const int BUSINESS_SLUG_MAX_LENGTH = 63;
    public const int USER_NAME_MAX_LENGTH = 255;
    public const int USER_EMAIL_MAX_LENGTH = 255;

    // ─────────────────────────────────────────────────────────────────
    // Required Directories
    // ─────────────────────────────────────────────────────────────────

    /** @var array<string, string> - [path => friendly_name] */
    public const array REQUIRED_DIRECTORIES = [
        PLATFORM_DIR => 'Platform directory',
        PLATFORM_STORAGE_PATH => 'Storage directory',
        WEBKERNEL_CACHE_PATH => 'Cache directory',
    ];

    // ─────────────────────────────────────────────────────────────────
    // Error Messages
    // ─────────────────────────────────────────────────────────────────

    public const string ERROR_REQUIREMENTS_NOT_MET = 'Installation requirements are not met. Please fix all failing requirements.';
    public const string ERROR_INSTALLATION_FAILED = 'Installation failed. Check the output above for details.';
    public const string ERROR_INVALID_TOKEN = 'Invalid Setup Token. Please try again.';
    public const string ERROR_SETUP_FAILED = 'Setup failed. Please check the error message above.';
    public const string ERROR_DIRECTORY_NOT_EXIST = 'Directory does not exist: %s';
    public const string ERROR_DIRECTORY_NOT_WRITABLE = 'Directory is not writable: %s';
    public const string ERROR_DIRECTORY_CREATE_FAILED = 'Cannot create directory: %s';
    public const string ERROR_TOKEN_FILE_CREATE_FAILED = 'Cannot write token file: %s';
    public const string ERROR_TOKEN_FILE_READ_FAILED = 'Cannot read token file: %s';
    public const string ERROR_TOKEN_DIR_CREATE_FAILED = 'Cannot create token directory: %s';

    // ─────────────────────────────────────────────────────────────────
    // Success Messages
    // ─────────────────────────────────────────────────────────────────

    public const string SUCCESS_INSTALLATION_COMPLETE = 'Infrastructure ready. Proceed with setup configuration.';
    public const string SUCCESS_SETUP_COMPLETE = 'Welcome, %s — setup complete';
    public const string SUCCESS_INSTALLATION_RESUMED = 'Infrastructure is ready — complete the wizard to finish setup.';

    // ─────────────────────────────────────────────────────────────────
    // Notifications
    // ─────────────────────────────────────────────────────────────────

    public const string NOTIFICATION_INFRASTRUCTURE_READY = 'Infrastructure ready';
    public const string NOTIFICATION_INSTALLATION_FAILED = 'Installation encountered an error';
    public const string NOTIFICATION_INSTALLATION_RESUMED = 'Installation resumed';
    public const string NOTIFICATION_REQUIREMENTS_NOT_MET = 'Requirements not met';
    public const string NOTIFICATION_INVALID_TOKEN = 'Invalid Setup Token';
    public const string NOTIFICATION_SETUP_FAILED = 'Setup failed';
    public const string NOTIFICATION_MAILER_NOT_SAVED = 'Mailer not saved';
    public const string NOTIFICATION_BUSINESS_NOT_CREATED = 'Business not created';
    public const string NOTIFICATION_INVITATION_NOT_SENT = 'Invitation not sent';

    // ─────────────────────────────────────────────────────────────────
    // UI Text
    // ─────────────────────────────────────────────────────────────────

    public const string BUTTON_INSTALL = 'Install Webkernel';
    public const string BUTTON_VALIDATE = 'Validate Token';
    public const string BUTTON_RETRY = 'Retry';
    public const string BUTTON_COMPLETE = 'Complete setup';
    public const string BUTTON_COMPLETING = 'Setting up…';

    public const string CONFIRMATION_HEADING = 'Start installation';
    public const string CONFIRMATION_BODY = 'This will copy .env, generate the app key, create the SQLite database, run all migrations, and write deployment.php. This cannot be undone.';
    public const string CONFIRMATION_SUBMIT = 'Yes, install now';

    public const string SUBHEADING_PRE = 'Pre-flight checks — requirements and capabilities';
    public const string SUBHEADING_INSTALLING = 'Installation in progress…';
    public const string SUBHEADING_VERIFY_TOKEN = 'Enter your one-time Setup Token to continue';
    public const string SUBHEADING_SETUP = 'Complete the setup wizard';
    public const string SUBHEADING_ERROR = 'Installation encountered an error';

    // ─────────────────────────────────────────────────────────────────
    // Email Templates
    // ─────────────────────────────────────────────────────────────────

    public const string EMAIL_SUBJECT_INVITATION = 'You have been invited to manage %s';
    public const string EMAIL_BODY_INVITATION = '<p>You have been invited to manage <strong>%s</strong>.</p><p><a href="%s">%s</a></p>';
}
