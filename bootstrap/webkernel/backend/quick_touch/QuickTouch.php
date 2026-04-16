<?php declare(strict_types=1);

namespace Webkernel\QuickTouch;

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Collection;
use Webkernel\QuickTouch\Registry\ActionRegistry;
use Webkernel\QuickTouch\Registry\ContextMenuRegistry;
use Webkernel\Traits\HasSelfResolvedView;

/**
 * QuickTouch — Central orchestrator for the Webkernel floating assistant.
 *
 * All boot logic, render-hook registration, and extension points live here.
 * The service provider simply calls:
 *
 *   \Webkernel\QuickTouch\QuickTouch::bootQuickTouch();
 *
 * ──────────────────────────────────────────────────────────────────────────
 * Extension points
 * ──────────────────────────────────────────────────────────────────────────
 *
 * QuickTouch::addGlobalAction(QuickTouchAction $action)
 *   → always visible in the "Main" tab quick-grid
 *
 * QuickTouch::addContextMenuItem(ContextMenuItem $item)
 *   → appended to the right-click / footer context menu
 *
 * QuickTouch::forResource(string $resource, callable $callback)
 *   → $callback receives a QuickTouchBuilder and can push resource-scoped
 *     actions, row actions, and context items
 *
 * QuickTouch::disable()  /  QuickTouch::enable()
 *   → global kill-switch (useful in test suites or public panels)
 */
class QuickTouch
{
    public const string QUICKTOUCH_VERSION = '1.0.0';

    /** Global enabled flag */
    private static bool $enabled = true;

    /** @var array<string, callable> resource-scoped builder callbacks */
    private static array $resourceCallbacks = [];

    // ── public API ──────────────────────────────────────────────────────────

    public static function version(): string { return self::QUICKTOUCH_VERSION; }

    public static function enable(): void  { self::$enabled = true; }
    public static function disable(): void { self::$enabled = false; }
    public static function isEnabled(): bool { return self::$enabled; }

    /**
     * Register a per-resource configuration callback.
     *
     * @param  string    $resource  FQCN of the Filament resource
     * @param  callable  $callback  fn(QuickTouchBuilder $b): void
     */
    public static function forResource(string $resource, callable $callback): void
    {
        self::$resourceCallbacks[$resource] = $callback;
    }

    /** @return array<string, callable> */
    public static function getResourceCallbacks(): array
    {
        return self::$resourceCallbacks;
    }

    // ── boot ────────────────────────────────────────────────────────────────

    /**
     * Called once from the service-provider boot method.
     */
    public static function bootQuickTouch(): void
    {
        if (! static::$enabled) {
            return;
        }

        static::registerRenderHook();
    }

    // ── render hook ─────────────────────────────────────────────────────────

    private static function registerRenderHook(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            static function (): \Illuminate\Contracts\View\View {
                return view('webkernel-quick-touch::quick-touch', static::buildViewData());
            },
        );
    }

    // ── view data ───────────────────────────────────────────────────────────

    /**
     * Assemble every piece of data the Blade view needs.
     *
     * @return array<string, mixed>
     */
    public static function buildViewData(): array
    {
        $panels    = static::resolvePanels();
        $user      = static::resolveUser();
        $favorites = static::resolveFavorites($user);
        $enabled   = static::resolveEnabledForUser($user);

        return [
            // raw PHP arrays — used inside @php blocks
            'wktEnabled'   => $enabled,
            'wktPanels'    => $panels,
            'wktUser'      => $user,
            'wktFavorites' => $favorites,

            // JSON strings — written into <script> tags
            'wktPanelsJson'    => json_encode($panels,    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'wktFavoritesJson' => json_encode($favorites, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'wktUserJson'      => json_encode($user,      JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),

            // trait detection flag
            'wktHasTrait' => static::userHasTrait($user),

            // extension registries
            'wktGlobalActions'   => ActionRegistry::global(),
            'wktContextItems'    => ContextMenuRegistry::items(),
        ];
    }

    // ── resolvers ───────────────────────────────────────────────────────────

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private static function resolvePanels(): array
    {
        return collect(\Filament\Facades\Filament::getPanels())
            ->map(fn ($panel) => [
                'label' => $panel->getId(),
                'url'   => $panel->getUrl() ?? '/',
            ])
            ->values()
            ->toArray();
    }

    /**
     * @return array{name: string|null, email: string|null}|null
     */
    private static function resolveUser(): ?array
    {
        if (! filament()->auth()->check()) {
            return null;
        }

        $u = filament()->auth()->user();

        return [
            'name'  => $u->name  ?? $u->email ?? null,
            'email' => $u->email ?? null,
        ];
    }

    /**
     * @return array<int, array{url: string, title: string}>
     */
    private static function resolveFavorites(?array $user): array
    {
        if (! filament()->auth()->check()) {
            return [];
        }

        $authUser = filament()->auth()->user();

        // DB-persisted favorites via HasQuickTouch trait
        if (method_exists($authUser, 'getQuickTouchFavorites')) {
            return $authUser->getQuickTouchFavorites();
        }

        // Legacy trait name kept for backwards compat
        if (method_exists($authUser, 'getWebkernelTouchFavorites')) {
            return $authUser->getWebkernelTouchFavorites();
        }

        return [];
    }

    private static function resolveEnabledForUser(?array $user): bool
    {
        if (! static::$enabled) {
            return false;
        }

        if (! filament()->auth()->check()) {
            return true; // guest — let the view decide
        }

        $authUser = filament()->auth()->user();

        if (method_exists($authUser, 'hasQuickTouchEnabled')) {
            return $authUser->hasQuickTouchEnabled();
        }

        // Legacy
        if (method_exists($authUser, 'hasWebkernelTouchEnabled')) {
            return $authUser->hasWebkernelTouchEnabled();
        }

        return true;
    }

    private static function userHasTrait(?array $user): bool
    {
        if (! filament()->auth()->check()) {
            return false;
        }

        return method_exists(filament()->auth()->user(), 'getQuickTouchFavorites')
            || method_exists(filament()->auth()->user(), 'getWebkernelTouchFavorites');
    }
}
