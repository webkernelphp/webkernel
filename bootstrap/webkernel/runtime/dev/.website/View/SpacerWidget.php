<?php

declare(strict_types=1);

namespace Webkernel\Builders\Website\View;

use Filament\Forms\Components\Select;

class SpacerWidget extends BaseWidget
{
    public static function getType(): string
    {
        return 'spacer';
    }

    public static function getLabel(): string
    {
        return __('layup::widgets.labels.spacer');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-arrows-up-down';
    }

    public static function getCategory(): string
    {
        return 'layout';
    }

    public static function getContentFormSchema(): array
    {
        return [
            Select::make('height')
                ->label(__('layup::widgets.spacer.height'))
                ->options(['1rem' => __('layup::widgets.spacer.extra_small_1rem'),
                    '2rem' => __('layup::widgets.spacer.small_2rem'),
                    '3rem' => __('layup::widgets.spacer.medium_3rem'),
                    '4rem' => __('layup::widgets.spacer.large_4rem'),
                    '6rem' => __('layup::widgets.spacer.extra_large_6rem'),
                    '8rem' => __('layup::widgets.spacer.huge_8rem'), ])
                ->default('2rem'),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'height' => '2rem',
        ];
    }

    public static function getPreview(array $data): string
    {
        return '↕ Spacer · ' . ($data['height'] ?? '2rem');
    }
}
