<?php

namespace Webkernel\Builders\DBStudio\Database\Factories;

use Webkernel\Builders\DBStudio\Models\StudioCollection;
use Webkernel\Builders\DBStudio\Models\StudioRecord;
use Webkernel\Builders\DBStudio\Models\StudioRecordVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudioRecordVersion>
 */
class StudioRecordVersionFactory extends Factory
{
    protected $model = StudioRecordVersion::class;

    public function definition(): array
    {
        return [
            'record_id' => StudioRecord::factory(),
            'collection_id' => StudioCollection::factory(),
            'tenant_id' => null,
            'snapshot' => ['name' => $this->faker->word()],
            'created_by' => null,
            'created_at' => now(),
        ];
    }

    /**
     * Ensure the version's record belongs to the same collection.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (StudioRecordVersion $version) {
            if ($version->record->collection_id !== $version->collection_id) {
                $version->record->update(['collection_id' => $version->collection_id]);
            }
        });
    }
}
