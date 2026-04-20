<?php

declare(strict_types=1);

namespace Webkernel\Builders\Website\View;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;

class MasonryWidget extends BaseWidget
{
    public static function getType(): string
    {
        return 'masonry';
    }

    public static function getLabel(): string
    {
        return __('layup::widgets.labels.masonry');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-squares-2x2';
    }

    public static function getCategory(): string
    {
        return 'media';
    }

    public static function getContentFormSchema(): array
    {
        return [
            FileUpload::make('images')
                ->label(__('layup::widgets.masonry.images'))
                ->image()
                ->multiple()
                ->reorderable()
                ->directory('layup/masonry')
                ->columnSpanFull(),
            Select::make('columns')
                ->label(__('layup::widgets.masonry.columns'))
                ->options(['2' => __('layup::widgets.masonry.2'), '3' => __('layup::widgets.masonry.3'), '4' => __('layup::widgets.masonry.4'), '5' => __('layup::widgets.masonry.5')])
                ->default('3'),
            Select::make('gap')
                ->label(__('layup::widgets.masonry.gap'))
                ->options(['0.25rem' => __('layup::widgets.masonry.extra_small'),
                    '0.5rem' => __('layup::widgets.masonry.small'),
                    '1rem' => __('layup::widgets.masonry.medium'),
                    '1.5rem' => __('layup::widgets.masonry.large'), ])
                ->default('0.5rem'),
            Toggle::make('rounded')
                ->label(__('layup::widgets.masonry.rounded_corners'))
                ->default(true),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'images' => [],
            'columns' => '3',
            'gap' => '0.5rem',
            'rounded' => true,
        ];
    }

    public static function getPreview(array $data): string
    {
        $count = count($data['images'] ?? []);

        return "🧱 Masonry ({$count} images)";
    }
}
