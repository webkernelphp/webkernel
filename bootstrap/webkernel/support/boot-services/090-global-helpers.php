<?php declare(strict_types=1);

// ═══════════════════════════════════════════════════════════════════
//  § 8  Global helpers
// ═══════════════════════════════════════════════════════════════════

if (! function_exists('webkernel_page')) {
    /**
     * Return a fresh EmergencyPageBuilder (full-page mode).
     * Available globally — works inside Filament, Blade, raw PHP, anywhere.
     */
    function webkernel_page(): EmergencyPageBuilder
    {
        return EmergencyPageBuilder::create();
    }
}

if (! function_exists('webkernel_modal')) {
    /**
     * Return a fresh EmergencyPageBuilder (modal mode).
     * Renders an HTML fragment — embed it anywhere in your views.
     */
    function webkernel_modal(): EmergencyPageBuilder
    {
        return EmergencyPageBuilder::modal();
    }
}

if (! function_exists('webkernel_abort')) {
    /**
     * Shorthand — render a critical error page and terminate.
     */
    function webkernel_abort(string $message, int $code = 500, string $severity = 'CRITICAL'): never
    {
        EmergencyPageBuilder::create()
            ->message($message)
            ->code($code)
            ->severity($severity)
            ->render();
    }
}
