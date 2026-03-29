<?php

declare(strict_types=1);

namespace Webkernel\Widgets;

use Filament\Widgets\ChartWidget;
use Webkernel\Widgets\Contracts\ConfigurableWidget;

class ActiveProcesses extends ChartWidget implements ConfigurableWidget
{
    protected ?string $heading = 'Processes';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    public static function getWidgetType(): string
    {
        return 'chart.line';
    }

    public static function getLabel(): string
    {
        return 'Processes';
    }

    public static function getDefaultConfig(): array
    {
        return [
            'type' => 'line',
        ];
    }

    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Processes',
                    'data' => [10, 15, 8, 12, 20],
                ],
            ],
            'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
