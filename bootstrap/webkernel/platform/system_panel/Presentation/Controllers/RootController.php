<?php declare(strict_types=1);

namespace Webkernel\Platform\SystemPanel\Presentation\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Webkernel\Platform\SystemPanel\Support\InstallationState;

/**
 * Handles the application root (/).
 *
 * Resolution order:
 *   NOT_INSTALLED -> /installer         (run the full wizard)
 *   MISSING_ADMIN -> /installer         (resume at create_user)
 *   INSTALLED     -> /system            (normal boot)
 *
 * Both NOT_INSTALLED and MISSING_ADMIN land on /installer.
 * The InstallerPage::mount() method reads InstallationState itself and
 * sets the correct phase, so no state needs to be passed in the URL.
 */
final class RootController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return match (InstallationState::resolve()) {
            InstallationState::INSTALLED => redirect('/system'),
            default                      => redirect('/installer'),
        };
    }
}
