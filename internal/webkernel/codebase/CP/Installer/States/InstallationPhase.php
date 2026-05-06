<?php declare(strict_types=1);

// ─────────────────────────────────────────────────────────────────────────────
// INSTALLER CONSTANTS - PHP 8.4+ with strong typing
// ─────────────────────────────────────────────────────────────────────────────

namespace Webkernel\CP\Installer\Constants;

/**
 * Installation phase constants
 */
enum InstallationPhase: string
{
    case PRE = 'pre';
    case INSTALLING = 'installing';
    case VERIFY_TOKEN = 'verify_token';
    case SETUP = 'setup';
    case ERROR = 'error';
}
