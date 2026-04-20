<?php

namespace Webkernel\Builders\Website\Database\Seeders;

use Webkernel\Builders\Website\Models\Page;
use Illuminate\Database\Seeder;

class LayupSeeder extends Seeder
{
    public function run(): void
    {
        // Sample homepage
        Page::updateOrCreate(
            ['slug' => 'home'],
            [
                'title' => 'Home',
                'status' => 'published',
                'content' => [
                    'rows' => [
                        [
                            'id' => 'row_hero',
                            'settings' => ['gap' => 'gap-0'],
                            'columns' => [
                                [
                                    'id' => 'col_hero',
                                    'span' => ['sm' => 12, 'md' => 12, 'lg' => 12, 'xl' => 12],
                                    'settings' => ['padding' => 'p-8', 'background' => '#1e293b'],
                                    'widgets' => [
                                        [
                                            'id' => 'widget_hero_heading',
                                            'type' => 'heading',
                                            'data' => ['content' => 'Welcome to Layup', 'level' => 'h1'],
                                        ],
                                        [
                                            'id' => 'widget_hero_text',
                                            'type' => 'text',
                                            'data' => ['content' => 'A flexible page builder for Filament.'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'id' => 'row_features',
                            'settings' => ['gap' => 'gap-6'],
                            'columns' => [
                                [
                                    'id' => 'col_feat1',
                                    'span' => ['sm' => 12, 'md' => 4, 'lg' => 4, 'xl' => 4],
                                    'settings' => ['padding' => 'p-4'],
                                    'widgets' => [
                                        [
                                            'id' => 'widget_feat1',
                                            'type' => 'heading',
                                            'data' => ['content' => 'Drag & Drop', 'level' => 'h3'],
                                        ],
                                    ],
                                ],
                                [
                                    'id' => 'col_feat2',
                                    'span' => ['sm' => 12, 'md' => 4, 'lg' => 4, 'xl' => 4],
                                    'settings' => ['padding' => 'p-4'],
                                    'widgets' => [
                                        [
                                            'id' => 'widget_feat2',
                                            'type' => 'heading',
                                            'data' => ['content' => 'Responsive', 'level' => 'h3'],
                                        ],
                                    ],
                                ],
                                [
                                    'id' => 'col_feat3',
                                    'span' => ['sm' => 12, 'md' => 4, 'lg' => 4, 'xl' => 4],
                                    'settings' => ['padding' => 'p-4'],
                                    'widgets' => [
                                        [
                                            'id' => 'widget_feat3',
                                            'type' => 'heading',
                                            'data' => ['content' => 'Extensible', 'level' => 'h3'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        // Generate some random pages
        Page::factory()->count(5)->create();
    }
}
