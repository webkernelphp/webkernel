<?php

declare(strict_types=1);

namespace Webkernel\Builders\Website\View;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class ToggleWidget extends BaseWidget
{
    public static function getType(): string
    {
        return 'toggle';
    }

    public static function getLabel(): string
    {
        return __('layup::widgets.labels.toggle');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-chevron-down';
    }

    public static function getCategory(): string
    {
        return 'content';
    }

    public static function getContentFormSchema(): array
    {
        return [
            TextInput::make('title')
                ->label(__('layup::widgets.toggle.title'))
                ->required(),
            RichEditor::make('content')
                ->label(__('layup::widgets.toggle.content'))
                ->columnSpanFull(),
            Toggle::make('open')
                ->label(__('layup::widgets.toggle.open_by_default'))
                ->default(false),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'title' => '',
            'content' => '',
            'open' => false,
        ];
    }

    public static function getPreview(array $data): string
    {
        $title = $data['title'] ?? '';

        return $title ? "▸ {$title}" : '(empty toggle)';
    }
}
