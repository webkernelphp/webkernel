<?php

namespace Webkernel\Base\Builders\DBStudio\Widgets;

use Filament\Widgets\Widget;
use Webkernel\Base\Builders\DBStudio\Concerns\InteractsWithPanelConfig;
use Webkernel\Base\Builders\DBStudio\Models\StudioPanel;

abstract class AbstractStudioWidget extends Widget
{
    use InteractsWithPanelConfig;

    protected int|string|array $columnSpan = 'full';

    public function mount(StudioPanel $panel, array $variables = [], ?string $recordUuid = null): void
    {
        $this->mountInteractsWithPanelConfig($panel, $variables, $recordUuid);
    }

    public function getHeading(): ?string
    {
        return $this->getPanelHeading();
    }

    public function getDescription(): ?string
    {
        return $this->getPanelDescription();
    }
}
