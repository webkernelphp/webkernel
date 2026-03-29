<?php

declare(strict_types=1);

namespace Webkernel\Widgets\Contracts;

interface ConfigurableWidget
{
    public static function getWidgetType(): string;

    public static function getLabel(): string;

    public static function getDefaultConfig(): array;
}
