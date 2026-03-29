<?php

declare(strict_types=1);

namespace Webkernel\Pages\Dashboard\Concerns;

use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Filament\Widgets\WidgetConfiguration;

trait ResolvesWidgets
{
    protected function getWidgetDefinitions(): array
    {
        return [
            ...Filament::getWidgets(),
            ...$this->getStaticWidgets(),
        ];
    }

    protected function getStaticWidgets(): array
    {
        return [
            \Webkernel\Widgets\SystemStats::class,
           // \Webkernel\Widgets\LatestLogs::class,
            \Webkernel\Widgets\ActiveProcesses::class,
        ];
    }

    protected function resolveWidgetComponents(): array
    {
        return $this->getWidgetsSchemaComponents(
            $this->filterVisibleWidgets(
                $this->getWidgetDefinitions()
            )
        );
    }
}
