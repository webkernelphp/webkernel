<?php

declare(strict_types=1);

namespace Webkernel\Builders\Website\View;

use Webkernel\Builders\Website\Contracts\Widget;
use Webkernel\Builders\Website\Support\WidgetContext;
use Illuminate\Contracts\View\View;

abstract class BaseWidget extends BaseView implements Widget
{
    abstract public static function getType(): string;

    abstract public static function getLabel(): string;

    /**
     * Widget-specific content fields.
     * Every widget must override this.
     *
     * @return array<\Filament\Forms\Components\Component>
     */
    public static function getContentFormSchema(): array
    {
        return [];
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-puzzle-piece';
    }

    public static function getCategory(): string
    {
        return 'content';
    }

    public static function getDefaultData(): array
    {
        return [];
    }

    /**
     * Generate preview text for the builder canvas.
     * Override in subclasses for richer previews.
     */
    public static function getPreview(array $data): string
    {
        if (! empty($data['content'])) {
            $text = strip_tags((string) $data['content']);

            return mb_strlen($text) > 60 ? mb_substr($text, 0, 60) . '…' : $text;
        }

        if (! empty($data['label'])) {
            return $data['label'];
        }

        if (! empty($data['src'])) {
            return '🖼 ' . basename((string) $data['src']);
        }

        return '(empty)';
    }

    /**
     * Called after save. Override to transform or validate data.
     * Context is provided when available (page, row/column/widget IDs).
     */
    public static function onSave(array $data, ?WidgetContext $context = null): array
    {
        return $data;
    }

    /**
     * Called on widget creation. Override for init logic.
     * Context is provided when available.
     */
    public static function onCreate(array $data, ?WidgetContext $context = null): array
    {
        return $data;
    }

    /**
     * Called on widget deletion. Override for cleanup.
     * Context is provided when available.
     */
    public static function onDelete(array $data, ?WidgetContext $context = null): void
    {
        // No-op by default
    }

    public static function toArray(): array
    {
        return [
            'type' => static::getType(),
            'label' => static::getLabel(),
            'icon' => static::getIcon(),
            'category' => static::getCategory(),
            'defaults' => static::getDefaultData(),
        ];
    }

    /**
     * Get the view name for frontend rendering.
     * Convention: layup::components.{type}
     * Override for custom view paths.
     */
    protected function getViewName(): string
    {
        return 'layup::components.' . static::getType();
    }

    public function render(): View
    {
        return view($this->getViewName(), [
            'data' => $this->data,
            'children' => $this->children,
        ]);
    }
}
