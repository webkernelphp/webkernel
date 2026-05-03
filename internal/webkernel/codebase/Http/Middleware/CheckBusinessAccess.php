<?php declare(strict_types=1);

namespace Webkernel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Webkernel\Base\Businesses\Models\Business;
use Webkernel\Base\Domains\Enum\PanelType;

/**
 * CheckBusinessAccess — gates access to a Business Panel.
 *
 * Runs after ResolveDomainContext. Reads the domain context set by that
 * middleware, verifies the panel type is 'business', then checks that the
 * authenticated user has access to the resolved business.
 *
 * On success, business_id is confirmed in request attributes so downstream
 * resources can scope queries without re-fetching the domain.
 */
class CheckBusinessAccess
{
    public function handle(Request $request, Closure $next): mixed
    {
        /** @var \Webkernel\Base\Domains\Models\Domain|null $domain */
        $domain = $request->attributes->get('domain');

        if (! $domain || $domain->panel_type !== PanelType::BUSINESS) {
            abort(403, 'Business Panel access denied');
        }

        $user = auth()->user();

        if (! $user) {
            return redirect()->guest(route('filament.business.auth.login'));
        }

        /** @var Business $business */
        $business = $request->attributes->get('business');

        if (! $business || ! $this->userCanAccessBusiness($user, $business)) {
            abort(403, 'Access denied');
        }

        $request->attributes->set('business_id', $business->id);

        return $next($request);
    }

    private function userCanAccessBusiness(mixed $user, Business $business): bool
    {
        // App Owner and Super Admins can access any business panel.
        if (method_exists($user, 'isAppOwner') && $user->isAppOwner()) {
            return true;
        }

        // Business-level users: check the business_id on their profile.
        // This is intentionally kept simple — module-level RBAC is handled
        // by each module's own guard/policy.
        if (isset($user->business_id)) {
            return $user->business_id === $business->id;
        }

        return false;
    }
}
