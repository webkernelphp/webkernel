<?php

namespace Webkernel\Builders\DBStudio\Widgets;

use Filament\Widgets\ChartWidget;
use Webkernel\Builders\DBStudio\Concerns\InteractsWithPanelConfig;
use Webkernel\Builders\DBStudio\Enums\AggregateFunction;
use Webkernel\Builders\DBStudio\Enums\GroupPrecision;
use Webkernel\Builders\DBStudio\Models\StudioCollection;
use Webkernel\Builders\DBStudio\Models\StudioPanel;
use Webkernel\Builders\DBStudio\Services\EavQueryBuilder;

class TimeSeriesWidget extends ChartWidget
{
    use InteractsWithPanelConfig;

    protected int|string|array $columnSpan = 'full';

    public function mount(?StudioPanel $panel = null, array $variables = [], ?string $recordUuid = null): void
    {
        if ($panel !== null) {
            $this->mountInteractsWithPanelConfig($panel, $variables, $recordUuid);
        }

        parent::mount();
    }

    public function getHeading(): ?string
    {
        return $this->getPanelHeading();
    }

    public function getDescription(): ?string
    {
        return $this->getPanelDescription();
    }

    protected function getType(): string
    {
        return 'line';
    }

    public function getData(): array
    {
        $config = $this->resolvedConfig();
        $collectionId = $config['collection_id'] ?? null;
        $dateField = $config['date_field'] ?? null;
        $valueField = $config['value_field'] ?? null;
        $functionKey = $config['aggregate_function'] ?? 'count';
        $precisionKey = $config['group_precision'] ?? GroupPrecision::Day->value;

        if (! $collectionId || ! $dateField || ! $valueField) {
            return ['datasets' => [], 'labels' => []];
        }

        $collection = StudioCollection::find($collectionId);
        if (! $collection) {
            return ['datasets' => [], 'labels' => []];
        }

        $function = AggregateFunction::from($functionKey);
        $precision = GroupPrecision::from($precisionKey);

        $rows = EavQueryBuilder::for($collection)
            ->tenant($this->panel->tenant_id)
            ->aggregateTimeSeries($function, $valueField, $dateField, $precision);

        $labels = $rows->keys()->all();
        $values = $rows->values()->all();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $this->panel->header_label ?? '',
                    'data' => $values,
                ],
            ],
        ];
    }
}
