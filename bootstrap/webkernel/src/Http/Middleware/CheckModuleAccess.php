<?php declare(strict_types=1);

namespace Webkernel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Webkernel\Base\Businesses\Models\Business;
use Webkernel\Base\Users\Enums\PanelType;
use Webkernel\Base\Arcanes\Modules\Models\Module;

/**
 * CheckModuleAccess — gates access to a Module Panel.
 *
 * Runs after ResolveDomainContext. Verifies the domain panel type is 'module',
 * resolves the module record, then checks that the authenticated user has
 * access to both the business and the specific module.
 *
 * On success, business_id and module_id are confirmed in request attributes.
 */
class CheckModuleAccess
{
    public function handle(Request $request, Closure $next): mixed
    {
        /** @var \Webkernel\Base\Domains\Models\Domain|null $domain */
        $domain = $request->attributes->get('domain');

        if (! $domain || $domain->panel_type !== PanelType::MODULE) {
            abort(403, 'Module Panel access denied');
        }

        if (! $domain->module_id) {
            abort(500, 'Module domain missing module_id');
        }

        $user = auth()->user();

        if (! $user) {
            return redirect()->guest(route('filament.system.auth.login'));
        }

        /** @var Business $business */
        $business = $request->attributes->get('business');

        $module = Module::find($domain->module_id);

        if (! $business || ! $module) {
            abort(404);
        }

        if (! $this->userCanAccessModule($user, $business, $module)) {
            abort(403, 'Module access denied');
        }

        $request->attributes->set('business_id', $business->id);
        $request->attributes->set('module_id',   $module->id);

        return $next($request);
    }

    private function userCanAccessModule(mixed $user, Business $business, Module $module): bool
    {
        // App Owners can access all modules.
        if (method_exists($user, 'isAppOwner') && $user->isAppOwner()) {
            return true;
        }

        // Check the module is enabled for this business.
        $map = $business->modules()
                        ->where('module_id', $module->id)
                        ->wherePivot('is_enabled', true)
                        ->exists();

        return $map;
    }
}
