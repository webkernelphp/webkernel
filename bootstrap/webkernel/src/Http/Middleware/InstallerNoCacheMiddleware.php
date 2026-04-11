<?php declare(strict_types=1);

namespace Webkernel\Http\Middleware;

use Illuminate\Http\Request;
use Closure;

/**
 * Forces no-cache headers on all installer panel responses.
 *
 * Prevents the browser from caching the installer HTML page, which contains
 * the Livewire script URL (hashed from APP_KEY). Without this, a browser
 * that cached the page with an old APP_KEY would send Livewire requests to
 * a non-existent route (hash mismatch → 404).
 */
final class InstallerNoCacheMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if (method_exists($response, 'header')) {
            $response->header('Cache-Control', 'no-store, no-cache, must-revalidate');
            $response->header('Pragma', 'no-cache');
        }

        return $response;
    }
}
