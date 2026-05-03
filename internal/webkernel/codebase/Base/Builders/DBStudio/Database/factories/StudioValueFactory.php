<?php

namespace Webkernel\Base\Builders\DBStudio\Database\Factories;

use Webkernel\Base\Builders\DBStudio\Models\StudioField;
use Webkernel\Base\Builders\DBStudio\Models\StudioRecord;
use Webkernel\Base\Builders\DBStudio\Models\StudioValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudioValue>
 */
class StudioValueFactory extends Factory
{
    protected $model = StudioValue::class;

    public function definition(): array
    {
        return [
            'record_id' => StudioRecord::factory(),
            'field_id' => StudioField::factory(),
        ];
    }

    /**
     * Ensure the value's record and field belong to the same collection.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (StudioValue $value) {
            // If both are being created fresh, link them to the same collection
            if ($value->record && $value->field && $value->record->collection_id !== $value->field->collection_id) {
                $value->field->collection_id = $value->record->collection_id;
                $value->field->save();
            }
        });
    }

    public function withText(string $text): static
    {
        return $this->state(fn () => ['val_text' => $text]);
    }

    public function withInteger(int $integer): static
    {
        return $this->state(fn () => ['val_integer' => $integer]);
    }
}
