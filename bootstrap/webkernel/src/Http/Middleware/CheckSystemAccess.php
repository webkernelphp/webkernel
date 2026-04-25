<?php declare(strict_types=1);

namespace Webkernel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Webkernel\Users\Enum\UserPrivilegeLevel;

/**
 * CheckSystemAccess — gates the System Panel to App Owner and Super Admins.
 *
 * Runs after ResolveDomainContext. Does not require domain context because
 * the System Panel is only accessible from the root domain and this
 * middleware is registered directly on the system panel's middleware stack.
 */
class CheckSystemAccess
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->guest(route('filament.system.auth.login'));
        }

        $privilege = $user->privilege ?? null;

        if (! $privilege || ! $privilege->isAtLeast(UserPrivilegeLevel::SUPER_ADMIN)) {
            abort(403, 'System Panel access denied');
        }

        return $next($request);
    }
}
