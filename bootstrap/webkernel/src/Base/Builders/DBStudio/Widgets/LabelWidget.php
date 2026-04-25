<?php

namespace Webkernel\Base\Builders\DBStudio\Widgets;

class LabelWidget extends AbstractStudioWidget
{
    protected string $view = 'filament-studio::widgets.label-widget';

    protected int|string|array $columnSpan = 'full';
}
