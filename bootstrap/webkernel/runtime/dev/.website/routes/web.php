<?php

declare(strict_types=1);

use Webkernel\Builders\Website\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Layup Frontend Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by LayupServiceProvider when frontend routing is
| enabled (layup.frontend.enabled = true). The route prefix, middleware,
| and domain are all configurable.
|
| The wildcard {slug} captures nested paths like "docs/getting-started"
| so pages can use hierarchical slugs.
|
*/

$prefix = config('layup.frontend.prefix', 'pages');
$middleware = config('layup.frontend.middleware', ['web']);
$domain = config('layup.frontend.domain');

$route = Route::middleware($middleware);

if ($domain) {
    $route = $route->domain($domain);
}

$route->group(function () use ($prefix) {
    // Exact prefix match → homepage/index page (slug = '')
    Route::get($prefix, PageController::class)
        ->name('layup.page.index');

    // Wildcard catch-all for nested slugs
    Route::get("{$prefix}/{slug}", PageController::class)
        ->where('slug', '.*')
        ->name('layup.page.show');
});
