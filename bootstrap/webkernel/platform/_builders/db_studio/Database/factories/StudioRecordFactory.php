<?php

namespace Webkernel\Builders\DBStudio\Database\Factories;

use Webkernel\Builders\DBStudio\Models\StudioCollection;
use Webkernel\Builders\DBStudio\Models\StudioRecord;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StudioRecord>
 */
class StudioRecordFactory extends Factory
{
    protected $model = StudioRecord::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid()->toString(),
            'collection_id' => StudioCollection::factory(),
            'tenant_id' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function forTenant(int $tenantId): static
    {
        return $this->state(fn () => ['tenant_id' => $tenantId]);
    }
}
