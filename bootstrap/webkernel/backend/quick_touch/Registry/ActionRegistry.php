<?php declare(strict_types=1);

namespace Webkernel\QuickTouch\Registry;

use Webkernel\QuickTouch\QuickTouchAction;

/**
 * Collects QuickTouchAction instances registered by any package or the host app.
 *
 * Usage:
 *
 *   // Register a global action (always visible):
 *   ActionRegistry::register(
 *       QuickTouchAction::make('docs')
 *           ->label('Docs')
 *           ->icon('<svg …/>')
 *           ->url('https://docs.example.com', newTab: true)
 *   );
 *
 *   // Register a resource-scoped row action:
 *   ActionRegistry::register(
 *       QuickTouchAction::make('edit-row')
 *           ->label('Edit')
 *           ->forResource(UserResource::class)
 *           ->scope('row')
 *           ->onClick('console.log("edit")')
 *   );
 */
class ActionRegistry
{
    /** @var QuickTouchAction[] */
    private static array $actions = [];

    public static function register(QuickTouchAction $action): void
    {
        self::$actions[$action->getName()] = $action;
    }

    /**
     * All global actions (no resource / no scope restriction).
     *
     * @return QuickTouchAction[]
     */
    public static function global(): array
    {
        return array_values(
            array_filter(self::$actions, fn (QuickTouchAction $a) => $a->isGlobal())
        );
    }

    /**
     * Actions scoped to a specific resource + optional scope label.
     *
     * @return QuickTouchAction[]
     */
    public static function forResource(string $resource, ?string $scope = null): array
    {
        return array_values(
            array_filter(
                self::$actions,
                fn (QuickTouchAction $a) =>
                    $a->getResource() === $resource &&
                    ($scope === null || $a->getScope() === $scope),
            )
        );
    }

    /** @return QuickTouchAction[] */
    public static function all(): array
    {
        return array_values(self::$actions);
    }

    public static function flush(): void
    {
        self::$actions = [];
    }
}
