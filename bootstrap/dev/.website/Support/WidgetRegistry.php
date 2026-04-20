<?php

declare(strict_types=1);

namespace Webkernel\Builders\Website\Support;

use Webkernel\Builders\Website\Contracts\Widget;
use InvalidArgumentException;

class WidgetRegistry
{
    /** @var array<string, class-string<Widget>> */
    protected array $widgets = [];

    /**
     * Register a widget class. Must implement the Widget contract.
     */
    public function register(string $widgetClass): static
    {
        if (! is_subclass_of($widgetClass, Widget::class)) {
            throw new InvalidArgumentException(
                "{$widgetClass} must implement " . Widget::class
            );
        }

        $this->widgets[$widgetClass::getType()] = $widgetClass;

        return $this;
    }

    /**
     * Unregister a widget by type.
     */
    public function unregister(string $type): static
    {
        unset($this->widgets[$type]);

        return $this;
    }

    /**
     * Get the widget class for a given type.
     */
    public function get(string $type): ?string
    {
        return $this->widgets[$type] ?? null;
    }

    /**
     * Check if a widget type is registered.
     */
    public function has(string $type): bool
    {
        return isset($this->widgets[$type]);
    }

    /**
     * Get all registered widget classes keyed by type.
     */
    public function all(): array
    {
        return $this->widgets;
    }

    /**
     * Return widget metadata for the Alpine.js builder.
     */
    public function toJs(): array
    {
        return collect($this->widgets)
            ->map(fn (string $class) => $class::toArray())
            ->values()
            ->all();
    }

    /**
     * Get the Filament form schema for a given widget type.
     */
    public function getFormSchema(string $type): array
    {
        $class = $this->get($type);

        return $class ? $class::getFormSchema() : [];
    }

    /**
     * Get default data for a given widget type.
     */
    public function getDefaultData(string $type): array
    {
        $class = $this->get($type);

        return $class ? $class::getDefaultData() : [];
    }

    /**
     * Get preview text for a widget's data.
     */
    public function getPreview(string $type, array $data): string
    {
        $class = $this->get($type);

        return $class ? $class::getPreview($data) : '(unknown widget)';
    }

    /**
     * Run the onCreate callback for a widget type.
     */
    public function fireOnCreate(string $type, array $data, ?WidgetContext $context = null): array
    {
        $class = $this->get($type);

        return $class ? $class::onCreate($data, $context) : $data;
    }

    /**
     * Run the onSave callback for a widget type.
     */
    public function fireOnSave(string $type, array $data, ?WidgetContext $context = null): array
    {
        $class = $this->get($type);

        return $class ? $class::onSave($data, $context) : $data;
    }

    /**
     * Run the onDelete callback for a widget type.
     */
    public function fireOnDelete(string $type, array $data, ?WidgetContext $context = null): void
    {
        $class = $this->get($type);
        if ($class) {
            $class::onDelete($data, $context);
        }
    }

    /**
     * Get all widget types grouped by category.
     */
    public function grouped(): array
    {
        return collect($this->widgets)
            ->mapToGroups(fn (string $class, string $type): array => [$class::getCategory() => $class::toArray()])
            ->all();
    }
}
