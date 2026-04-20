<?php

declare(strict_types=1);

namespace Webkernel\Builders\Website\Forms\Components;

use Filament\Forms\Components\Field;

class SpanPicker extends Field
{
    protected string $view = 'layup::forms.components.span-picker';

    protected string $breakpointLabel = '';

    protected string $color = '#f59e0b';

    public function breakpointLabel(string $label): static
    {
        $this->breakpointLabel = $label;

        return $this;
    }

    public function color(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getBreakpointLabel(): string
    {
        return $this->breakpointLabel;
    }

    public function getColor(): string
    {
        return $this->color;
    }
}
