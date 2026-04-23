<?php

namespace Webkernel\Builders\DBStudio\Database\Factories;

use Webkernel\Builders\DBStudio\Models\StudioField;
use Webkernel\Builders\DBStudio\Models\StudioFieldOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudioFieldOption>
 */
class StudioFieldOptionFactory extends Factory
{
    protected $model = StudioFieldOption::class;

    public function definition(): array
    {
        return [
            'field_id' => StudioField::factory()->state(['field_type' => 'select']),
            'tenant_id' => null,
            'value' => $this->faker->unique()->slug(1),
            'label' => $this->faker->word(),
            'color' => null,
            'icon' => null,
            'sort_order' => 0,
        ];
    }
}
