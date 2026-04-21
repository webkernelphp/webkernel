<?php declare(strict_types=1);

namespace Webkernel\QuickTouch\Registry;

use Webkernel\QuickTouch\ContextMenuItem;

/**
 * Collects ContextMenuItem instances that will appear in the
 * QuickTouch right-click / footer context menu.
 *
 * Items are sorted by their `sort` value before being returned.
 */
class ContextMenuRegistry
{
    /** @var ContextMenuItem[] */
    private static array $items = [];

    public static function register(ContextMenuItem $item): void
    {
        self::$items[$item->getName()] = $item;
    }

    /**
     * All items, sorted ascending by sort-order.
     *
     * @return ContextMenuItem[]
     */
    public static function items(): array
    {
        $items = array_values(self::$items);

        usort($items, fn (ContextMenuItem $a, ContextMenuItem $b) => $a->getSort() <=> $b->getSort());

        return $items;
    }

    public static function flush(): void
    {
        self::$items = [];
    }
}
