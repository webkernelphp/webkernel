<?php

namespace Webkernel\Base\Builders\DBStudio\Services;

use Filament\Tables\Filters\BaseFilter;
use Webkernel\Base\Builders\DBStudio\FieldTypes\FieldTypeRegistry;
use Webkernel\Base\Builders\DBStudio\Models\StudioCollection;
use Webkernel\Base\Builders\DBStudio\Models\StudioField;
use Illuminate\Database\Eloquent\Collection;

class DynamicFiltersBuilder
{
    /**
     * Build a Filament table filters array from a collection's filterable fields.
     *
     * @return array<BaseFilter>
     */
    public static function build(StudioCollection $collection): array
    {
        $registry = app(FieldTypeRegistry::class);

        /** @var Collection<int, StudioField> $fields */
        $fields = $collection->fields()
            ->where('is_filterable', true)
            ->orderBy('sort_order')
            ->get();

        return $fields
            ->map(fn (StudioField $field) => $registry->make($field)->toFilter())
            ->filter()
            ->values()
            ->all();
    }
}
