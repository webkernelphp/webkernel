<?php

declare(strict_types=1);

namespace Webkernel\Builders\Website\View;

use Filament\Forms\Components\RichEditor;

class TextWidget extends BaseWidget
{
    public static function getType(): string
    {
        return 'text';
    }

    public static function getLabel(): string
    {
        return __('layup::widgets.labels.text');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-document-text';
    }

    public static function getCategory(): string
    {
        return 'content';
    }

    public static function getContentFormSchema(): array
    {
        return [
            RichEditor::make('content')
                ->label(__('layup::widgets.text.content'))
                ->toolbarButtons([
                    'bold', 'italic', 'underline', 'strike',
                    'link', 'orderedList', 'bulletList',
                    'blockquote', 'codeBlock',
                ])
                ->columnSpanFull(),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'content' => '',
        ];
    }
}
