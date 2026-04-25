<?php declare(strict_types=1);

namespace Webkernel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Webkernel\Base\Businesses\Models\Business;
use Webkernel\Base\Domains\Models\Domain;

/**
 * ResolveDomainContext — resolves the incoming Host header to a domain record.
 *
 * This is the entry-point middleware for multi-tenant routing. It runs on
 * every request and injects routing context into request attributes so that
 * downstream middleware (CheckBusinessAccess, CheckModuleAccess) and
 * controllers can read business_id, panel_type, and module_id without
 * performing additional DB lookups.
 *
 * On miss: returns a 404 view. Adjust as needed for custom error pages.
 */
class ResolveDomainContext
{
    public function handle(Request $request, Closure $next): mixed
    {
        $host = $request->getHost();

        $domain = Domain::where('domain', $host)
                        ->where('is_active', true)
                        ->first();

        if (! $domain) {
            return response()->view('errors.domain-not-found', ['host' => $host], 404);
        }

        $business = Business::find($domain->business_id);

        if (! $business) {
            return response()->view('errors.domain-not-found', ['host' => $host], 404);
        }

        $request->attributes->set('domain',      $domain);
        $request->attributes->set('business',    $business);
        $request->attributes->set('business_id', $domain->business_id);
        $request->attributes->set('panel_type',  $domain->panel_type->value);
        $request->attributes->set('module_id',   $domain->module_id);

        return $next($request);
    }
}
