<?php declare(strict_types=1);

namespace Webkernel\Aptitudes\System\Presentation\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

/**
 * Handles the application root (/).
 *
 * - Not installed → /installer
 * - Installed     → /system
 */
final class RootController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return $this->isInstalled()
            ? redirect('/system')
            : redirect('/installer');
    }

    private function isInstalled(): bool
    {
        if (! is_file(base_path('.env'))) {
            return false;
        }

        $key = trim((string) config('app.key', ''));

        return $key !== ''
            && str_starts_with($key, 'base64:')
            && strlen($key) > 30
            && is_file(base_path('deployment.php'));
    }
}
