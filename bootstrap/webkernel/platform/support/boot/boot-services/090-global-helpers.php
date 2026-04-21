<?php declare(strict_types=1);
// =============================================================================
//  § 8  Global helpers
//  All helpers follow the micro_webpage*() nomenclature.
// =============================================================================

// ── Full-page micro web page ──────────────────────────────────────────────────

if (!function_exists('micro_webpage')) {
    /**
     * Return a fresh MicroWebPage in full-page mode.
     * Works inside Filament, Blade, raw PHP, anywhere.
     *
     * Usage:
     *   micro_webpage()
     *       ->title('Oops')
     *       ->message('Something went wrong.')
     *       ->severity('CRITICAL')
     *       ->addButton('Go Home', '/')
     *       ->render();
     */
    function micro_webpage(): MicroWebPage
    {
        return MicroWebPage::create();
    }
}

// ── Modal fragment ────────────────────────────────────────────────────────────

if (!function_exists('micro_webpage_modal')) {
    /**
     * Return a fresh MicroWebPage in simple modal fragment mode.
     * Renders an HTML fragment — embed it anywhere in your views.
     * The modal matches the page design and respects the active light/dark theme.
     *
     * Usage:
     *   echo micro_webpage_modal()
     *       ->title('Confirm Delete')
     *       ->message('This action cannot be undone.')
     *       ->severity('WARNING')
     *       ->modalButton('Delete', '/delete/1', 'destructive')
     *       ->modalButton('Cancel', '#', 'cancel')
     *       ->renderModal();
     */
    function micro_webpage_modal(): MicroWebPage
    {
        return MicroWebPage::modal();
    }
}

// ── Debug overlay ─────────────────────────────────────────────────────────────

if (!function_exists('micro_webpage_debug')) {
    /**
     * Return a fresh MicroWebPage in debug overlay mode.
     *
     * The overlay is hidden by default (display:none).
     * It is opened by the "Debug" footer button, which MicroWebPage injects
     * into the page when the overlay is present in the DOM (DOMContentLoaded).
     * Press Escape or click "Dismiss" to close.
     *
     * Usage:
     *   echo micro_webpage_debug()
     *       ->withException(get_class($e), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString())
     *       ->withRequest(request()->fullUrl(), request()->method())
     *       ->withDebugMeta('Laravel', app()->version())
     *       ->withDebugMeta('PHP', PHP_VERSION)
     *       ->code(500)
     *       ->title('Server Error')
     *       ->severity('CRITICAL')
     *       ->renderToString();
     */
    function micro_webpage_debug(): MicroWebPage
    {
        return MicroWebPage::debugModal();
    }
}

// ── Abort helpers ─────────────────────────────────────────────────────────────

if (!function_exists('micro_webpage_abort')) {
    /**
     * Render a critical error page and terminate immediately.
     *
     * Usage:
     *   micro_webpage_abort('Permission denied.', 403, 'WARNING');
     */
    function micro_webpage_abort(string $message, int $code = 500, string $severity = 'CRITICAL'): never
    {
        MicroWebPage::create()
            ->message($message)
            ->code($code)
            ->severity($severity)
            ->render();
    }
}

// ── Semantic page shortcuts ───────────────────────────────────────────────────

if (!function_exists('micro_webpage_error')) {
    /**
     * Render a server error page and exit.
     *
     * Usage:
     *   micro_webpage_error('Database connection failed.');
     */
    function micro_webpage_error(string $message = 'An unexpected error occurred.', int $code = 500): never
    {
        MicroWebPage::create()->serverError($message)->code($code)->render();
    }
}

if (!function_exists('micro_webpage_not_found')) {
    /**
     * Render a 404 Not Found page and exit.
     *
     * Usage:
     *   micro_webpage_not_found('The resource you requested does not exist.');
     */
    function micro_webpage_not_found(string $message = 'The page you are looking for could not be found.'): never
    {
        MicroWebPage::create()->notFound($message)->render();
    }
}

if (!function_exists('micro_webpage_forbidden')) {
    /**
     * Render a 403 Forbidden page and exit.
     *
     * Usage:
     *   micro_webpage_forbidden('You do not have access to this section.');
     */
    function micro_webpage_forbidden(string $message = 'You do not have permission to access this resource.'): never
    {
        MicroWebPage::create()->forbidden($message)->render();
    }
}

if (!function_exists('micro_webpage_maintenance')) {
    /**
     * Render a 503 Maintenance page and exit.
     *
     * Usage:
     *   micro_webpage_maintenance('Back online in ~15 minutes.');
     */
    function micro_webpage_maintenance(string $message = 'The system is being updated. Please check back shortly.'): never
    {
        MicroWebPage::create()->maintenance($message)->render();
    }
}

if (!function_exists('micro_webpage_validate')) {
    /**
     * Run field validation and render a 422 page on failure.
     *
     * Usage:
     *   micro_webpage_validate($_POST, [
     *       'email' => 'required|email',
     *       'name'  => 'required|min:2',
     *   ]);
     */
    function micro_webpage_validate(array $data, array $rules): ServerSideValidator
    {
        $v = ServerSideValidator::check($data);
        foreach ($rules as $field => $rule) {
            $v->field($field, $rule);
        }
        return $v->renderOnFail();
    }
}
