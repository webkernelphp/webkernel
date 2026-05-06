<?php declare(strict_types=1);
namespace Webkernel\CP\Installer\States;

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
