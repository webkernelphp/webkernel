<?php

namespace Webkernel\Builders\Website\Database\Factories;

use Webkernel\Builders\Website\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'content' => $this->defaultContent(),
            'status' => fake()->randomElement(['draft', 'published']),
            'meta' => [
                'description' => fake()->sentence(),
            ],
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => 'published']);
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }

    protected function defaultContent(): array
    {
        return [
            'rows' => [
                [
                    'id' => 'row_' . Str::random(8),
                    'settings' => ['gap' => 'gap-4'],
                    'columns' => [
                        [
                            'id' => 'col_' . Str::random(8),
                            'span' => ['sm' => 12, 'md' => 6, 'lg' => 6, 'xl' => 6],
                            'settings' => ['padding' => 'p-4'],
                            'widgets' => [
                                [
                                    'id' => 'widget_' . Str::random(8),
                                    'type' => 'heading',
                                    'data' => ['content' => fake()->sentence(4), 'level' => 'h2'],
                                ],
                            ],
                        ],
                        [
                            'id' => 'col_' . Str::random(8),
                            'span' => ['sm' => 12, 'md' => 6, 'lg' => 6, 'xl' => 6],
                            'settings' => ['padding' => 'p-4'],
                            'widgets' => [
                                [
                                    'id' => 'widget_' . Str::random(8),
                                    'type' => 'text',
                                    'data' => ['content' => fake()->paragraph()],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
