<?php declare(strict_types=1);

namespace Webkernel\QuickTouch\Concerns;

use Webkernel\QuickTouch\QuickTouchAction;
use Webkernel\QuickTouch\Registry\ActionRegistry;

/**
 * InteractsWithQuickTouch
 *
 * Add this trait to any Filament Resource to expose per-resource,
 * per-table, per-row, and per-widget QuickTouch actions.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * Usage — inside your Resource class
 * ──────────────────────────────────────────────────────────────────────────
 *
 *   use Webkernel\QuickTouch\Concerns\InteractsWithQuickTouch;
 *
 *   class UserResource extends Resource
 *   {
 *       use InteractsWithQuickTouch;
 *
 *       public static function getQuickTouchActions(): array
 *       {
 *           return [
 *               QuickTouchAction::make('export-users')
 *                   ->label('Export')
 *                   ->scope('table')
 *                   ->onClick('alert("export!")'),
 *
 *               QuickTouchAction::make('edit-row')
 *                   ->label('Quick Edit')
 *                   ->scope('row')
 *                   ->onClick('console.log("row edit")'),
 *           ];
 *       }
 *   }
 *
 * The actions are auto-registered when the resource's service-provider boots
 * (Filament discovers resources automatically, so call
 * `static::registerQuickTouchActions()` inside your Resource's `boot()` or
 * in your package/app service-provider).
 */
trait InteractsWithQuickTouch
{
    /**
     * Override this method in your resource to define QuickTouch actions.
     *
     * @return QuickTouchAction[]
     */
    public static function getQuickTouchActions(): array
    {
        return [];
    }

    /**
     * Register all actions returned by `getQuickTouchActions()` into the
     * ActionRegistry, automatically scoping them to this resource.
     */
    public static function registerQuickTouchActions(): void
    {
        foreach (static::getQuickTouchActions() as $action) {
            ActionRegistry::register(
                $action->forResource(static::class)
            );
        }
    }
}
