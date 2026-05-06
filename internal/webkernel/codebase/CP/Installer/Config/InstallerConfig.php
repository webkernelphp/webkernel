<?php declare(strict_types=1);

namespace Webkernel\CP\Installer\Config;

/**
 * Installer configuration constants and constraints.
 *
 * Centralizes all validation rules, error messages, UI text,
 * and system requirements for the installation wizard.
 */
final class InstallerConfig
{
    // =========================================================================
    // VALIDATION CONSTRAINTS
    // =========================================================================

    public const int PASSWORD_MIN_LENGTH = 12;
    public const int PASSWORD_MAX_LENGTH = 255;
    public const int BUSINESS_NAME_MAX_LENGTH = 255;
    public const int BUSINESS_SLUG_MAX_LENGTH = 63;
    public const int USER_NAME_MAX_LENGTH = 255;
    public const int USER_EMAIL_MAX_LENGTH = 255;

    // =========================================================================
    // MAILER DEFAULTS
    // =========================================================================

    public const string MAILER_DEFAULT_PORT = '587';
    public const string MAILER_DEFAULT_ENCRYPTION = 'tls';

    /**
     * Available SMTP encryption methods.
     *
     * @var array<string, string>
     */
    public const array MAILER_ENCRYPTIONS = [
        'tls' => 'TLS',
        'ssl' => 'SSL',
        'none' => 'None',
    ];

    // =========================================================================
    // REQUIRED SYSTEM DIRECTORIES
    // =========================================================================

    /**
     * Directories that must exist and be writable for installation.
     *
     * @var array<string, string>
     */
    public const array REQUIRED_DIRECTORIES = [
        PLATFORM_DIR => 'Platform directory',
        PLATFORM_STORAGE_PATH => 'Storage directory',
        WEBKERNEL_CACHE_PATH => 'Cache directory',
    ];

    // =========================================================================
    // ERROR MESSAGES
    // =========================================================================

    public const string ERROR_REQUIREMENTS_NOT_MET = 'Installation requirements are not met. Please fix all failing requirements.';
    public const string ERROR_INSTALLATION_FAILED = 'Installation failed. Check the output above for details.';
    public const string ERROR_INVALID_TOKEN = 'Invalid Setup Token. Please try again.';
    public const string ERROR_SETUP_FAILED = 'Setup failed. Please check the error message above.';
    public const string ERROR_DIRECTORY_NOT_EXIST = 'Directory does not exist: %s';
    public const string ERROR_DIRECTORY_NOT_WRITABLE = 'Directory is not writable: %s';

    // =========================================================================
    // SUCCESS MESSAGES
    // =========================================================================

    public const string SUCCESS_INSTALLATION_COMPLETE = 'Infrastructure ready. Proceed with setup configuration.';
    public const string SUCCESS_SETUP_COMPLETE = 'Welcome, %s — setup complete';
    public const string SUCCESS_INSTALLATION_RESUMED = 'Infrastructure is ready — complete the wizard to finish setup.';

    // =========================================================================
    // NOTIFICATIONS
    // =========================================================================

    public const string NOTIFICATION_INFRASTRUCTURE_READY = 'Infrastructure ready';
    public const string NOTIFICATION_INSTALLATION_FAILED = 'Installation encountered an error';
    public const string NOTIFICATION_INSTALLATION_RESUMED = 'Installation resumed';
    public const string NOTIFICATION_REQUIREMENTS_NOT_MET = 'Requirements not met';
    public const string NOTIFICATION_INVALID_TOKEN = 'Invalid Setup Token';
    public const string NOTIFICATION_SETUP_FAILED = 'Setup failed';
    public const string NOTIFICATION_MAILER_NOT_SAVED = 'Mailer not saved';
    public const string NOTIFICATION_BUSINESS_NOT_CREATED = 'Business not created';
    public const string NOTIFICATION_INVITATION_NOT_SENT = 'Invitation not sent';

    // =========================================================================
    // UI TEXT - BUTTONS & ACTIONS
    // =========================================================================

    public const string BUTTON_INSTALL = 'Install Webkernel';
    public const string BUTTON_VALIDATE = 'Validate Token';
    public const string BUTTON_RETRY = 'Retry';
    public const string BUTTON_COMPLETE = 'Complete setup';
    public const string BUTTON_COMPLETING = 'Setting up...';

    // =========================================================================
    // UI TEXT - CONFIRMATIONS & HEADINGS
    // =========================================================================

    public const string CONFIRMATION_HEADING = 'Start installation';
    public const string CONFIRMATION_BODY = 'This will copy .env, generate the app key, create the SQLite database, run all migrations, and write deployment.php. This cannot be undone.';
    public const string CONFIRMATION_SUBMIT = 'Yes, install now';

    // =========================================================================
    // EMAIL TEMPLATES
    // =========================================================================

    public const string EMAIL_SUBJECT_INVITATION = 'You have been invited to manage %s';
    public const string EMAIL_BODY_INVITATION = '<p>You have been invited to manage <strong>%s</strong>.</p><p><a href="%s">%s</a></p>';

    // =========================================================================
    // SYSTEM REQUIREMENTS
    // =========================================================================

    /**
     * Build the list of system requirements to check.
     *
     * @return array<int, array{id: string, label: string, ok: bool, value: string}>
     */
    public static function buildRequirements(): array
    {
        return [
            [
                'id' => 'php',
                'label' => 'PHP >= 8.4',
                'ok' => version_compare(PHP_VERSION, '8.4.0', '>='),
                'value' => PHP_VERSION,
            ],
            [
                'id' => 'openssl',
                'label' => 'OpenSSL extension',
                'ok' => extension_loaded('openssl'),
                'value' => '',
            ],
            [
                'id' => 'pdo',
                'label' => 'PDO extension',
                'ok' => extension_loaded('pdo'),
                'value' => '',
            ],
            [
                'id' => 'mbstring',
                'label' => 'Mbstring extension',
                'ok' => extension_loaded('mbstring'),
                'value' => '',
            ],
            [
                'id' => 'xml',
                'label' => 'XML / DOM extension',
                'ok' => extension_loaded('xml'),
                'value' => '',
            ],
            [
                'id' => 'json',
                'label' => 'JSON extension',
                'ok' => extension_loaded('json'),
                'value' => '',
            ],
            [
                'id' => 'ctype',
                'label' => 'Ctype extension',
                'ok' => extension_loaded('ctype'),
                'value' => '',
            ],
            [
                'id' => 'bcmath',
                'label' => 'BCMath extension',
                'ok' => extension_loaded('bcmath'),
                'value' => '',
            ],
            [
                'id' => 'storage',
                'label' => 'storage/ writable',
                'ok' => is_writable(storage_path()),
                'value' => '',
            ],
            [
                'id' => 'cache',
                'label' => 'cache/ writable',
                'ok' => is_writable(base_path('internal/cache')),
                'value' => '',
            ],
        ];
    }

    /**
     * Check if all system requirements are met.
     */
    public static function allRequirementsMet(): bool
    {
        return collect(static::buildRequirements())
            ->every(fn (array $req): bool => $req['ok']);
    }

    /**
     * Validate that required directories exist and are writable.
     *
     * @throws \RuntimeException
     */
    public static function validateDirectories(): void
    {
        foreach (static::REQUIRED_DIRECTORIES as $path => $name) {
            if (!is_dir($path)) {
                throw new \RuntimeException(
                    sprintf(static::ERROR_DIRECTORY_NOT_EXIST, $path)
                );
            }

            if (!is_writable($path)) {
                throw new \RuntimeException(
                    sprintf(static::ERROR_DIRECTORY_NOT_WRITABLE, $path)
                );
            }
        }
    }
}
