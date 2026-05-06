<?php declare(strict_types=1);

namespace Webkernel\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Webkernel\CP\Installer\States\InstallationState;
use Webkernel\CP\Installer\States\InstallationConstants;

/**
 * Handles requests to the application root (/).
 *
 * Routes based on installation state:
 *   NOT_INSTALLED / MISSING_ADMIN  => /installer (run or resume the wizard)
 *   INSTALLED                       => /system (normal application boot)
 */
final class RootController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return match (InstallationState::resolve()) {
            InstallationConstants::STATE_INSTALLED => redirect(filament()->getPanel('system')->getUrl()),
            default => redirect(InstallationConstants::ROUTE_URL),
        };
    }
}
