<?php

namespace Webkernel\Base\Builders\DBStudio\Api\Resources;

use Webkernel\Base\Builders\DBStudio\Models\StudioCollection;
use Webkernel\Base\Builders\DBStudio\Models\StudioRecord;
use Webkernel\Base\Builders\DBStudio\Services\EavQueryBuilder;
use Webkernel\Base\Builders\DBStudio\Services\LocaleResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StudioRecord
 */
class RecordResource extends JsonResource
{
    protected ?StudioCollection $collection = null;

    public function setCollection(StudioCollection $collection): static
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $collection = $this->collection ?? $request->attributes->get('wdb_studio_collection');

        $locale = app(LocaleResolver::class)->resolve($collection);

        $data = EavQueryBuilder::for($collection)
            ->locale($locale)
            ->getRecordData($this->resource);

        return [
            'uuid' => $this->uuid,
            'data' => $data,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
