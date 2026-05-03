<?php
namespace Webkernel\View;

/**
 * Render hook identifiers exposed by Webkernel.
 *
 * Downstream packages or the host application can register callbacks
 * against these hooks to customise Webkernel's output without touching
 * any Webkernel source file:
 *
 *   // In AppServiceProvider::boot() or a Filament plugin:
 *
 *   use Filament\Support\Facades\FilamentView;
 *   use Webkernel\View\RenderHooks;
 *
 *   FilamentView::registerRenderHook(
 *       RenderHooks::AUTH_BG_LIGHT,
 *       fn () => 'https://cdn.example.com/auth-light.jpg',
 *   );
 *
 *   FilamentView::registerRenderHook(
 *       RenderHooks::AUTH_BG_DARK,
 *       fn () => 'https://cdn.example.com/auth-dark.jpg',
 *   );
 *
 * The hook must return a plain URL string (no HTML, no quotes).
 * If no hook is registered the built-in default asset is used.
 */
final class RenderHooks
{
    /**
     * URL of the auth page background image in light mode.
     * Hook must return a plain URL string.
     */
    public const AUTH_BG_LIGHT = 'webkernel::auth.bg.light';

    /**
     * URL of the auth page background image in dark mode.
     * Hook must return a plain URL string.
     */
    public const AUTH_BG_DARK = 'webkernel::auth.bg.dark';
}
