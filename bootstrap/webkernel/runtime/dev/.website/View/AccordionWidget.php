<?php

declare(strict_types=1);

namespace Webkernel\Builders\Website\View;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class AccordionWidget extends BaseWidget
{
    public static function getType(): string
    {
        return 'accordion';
    }

    public static function getLabel(): string
    {
        return __('layup::widgets.labels.accordion');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-bars-3-bottom-left';
    }

    public static function getCategory(): string
    {
        return 'content';
    }

    public static function getContentFormSchema(): array
    {
        return [
            Repeater::make('items')
                ->label(__('layup::widgets.accordion.items'))
                ->schema([
                    TextInput::make('title')
                        ->label(__('layup::widgets.accordion.title'))
                        ->required(),
                    RichEditor::make('content')
                        ->label(__('layup::widgets.accordion.content'))
                        ->columnSpanFull(),
                ])
                ->defaultItems(2)
                ->collapsible()
                ->columnSpanFull(),
            Toggle::make('open_first')
                ->label(__('layup::widgets.accordion.open_first_item_by_default'))
                ->default(true),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'items' => [
                ['title' => 'Item 1', 'content' => ''],
                ['title' => 'Item 2', 'content' => ''],
            ],
            'open_first' => true,
        ];
    }

    public static function getPreview(array $data): string
    {
        $count = count($data['items'] ?? []);

        return "▾ Accordion · {$count} items";
    }
}
