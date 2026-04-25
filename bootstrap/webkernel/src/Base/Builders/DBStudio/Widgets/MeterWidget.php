<?php

namespace Webkernel\Base\Builders\DBStudio\Widgets;

use Webkernel\Base\Builders\DBStudio\Enums\AggregateFunction;
use Webkernel\Base\Builders\DBStudio\Models\StudioCollection;
use Webkernel\Base\Builders\DBStudio\Models\StudioPanel;
use Webkernel\Base\Builders\DBStudio\Services\EavQueryBuilder;

class MeterWidget extends AbstractStudioWidget
{
    protected string $view = 'filament-studio::widgets.meter-widget';

    public function mount(StudioPanel $panel, array $variables = [], ?string $recordUuid = null): void
    {
        $this->mountInteractsWithPanelConfig($panel, $variables, $recordUuid);
    }

    public function getValue(): float
    {
        $config = $this->resolvedConfig();
        $collectionId = $config['collection_id'] ?? null;
        $fieldName = $config['field'] ?? null;
        $functionKey = $config['aggregate_function'] ?? 'count';

        if (! $collectionId || ! $fieldName) {
            return 0.0;
        }

        $collection = StudioCollection::find($collectionId);
        if (! $collection) {
            return 0.0;
        }

        $function = AggregateFunction::from($functionKey);

        $rawValue = EavQueryBuilder::for($collection)
            ->tenant($this->panel->tenant_id)
            ->aggregate($function, $fieldName);

        return (float) ($rawValue ?? 0);
    }

    public function getMaximum(): float
    {
        return (float) $this->config('maximum', 100);
    }

    public function getPercentage(): float
    {
        $maximum = $this->getMaximum();
        if ($maximum <= 0) {
            return 0.0;
        }

        return min(100.0, ($this->getValue() / $maximum) * 100);
    }
}
