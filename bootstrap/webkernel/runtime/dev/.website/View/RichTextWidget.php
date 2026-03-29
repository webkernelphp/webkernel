<?php

declare(strict_types=1);

namespace Webkernel\Builders\Website\View;

use Filament\Forms\Components\RichEditor;

class RichTextWidget extends BaseWidget
{
    public static function getType(): string
    {
        return 'rich-text';
    }

    public static function getLabel(): string
    {
        return __('layup::widgets.labels.rich-text');
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
                ->label(__('layup::widgets.rich-text.content'))
                ->toolbarButtons([
                    'bold', 'italic', 'underline', 'strike',
                    'h2', 'h3',
                    'bulletList', 'orderedList',
                    'link', 'blockquote', 'codeBlock',
                    'undo', 'redo',
                ])
                ->columnSpanFull(),
        ];
    }

    public static function getDefaultData(): array
    {
        return ['content' => ''];
    }

    public static function getPreview(array $data): string
    {
        $content = strip_tags($data['content'] ?? '');

        return mb_strlen($content) > 60 ? mb_substr($content, 0, 60) . '…' : ($content ?: '(empty)');
    }
}
