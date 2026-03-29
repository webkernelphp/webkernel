<?php

declare(strict_types=1);

namespace Webkernel\Builders\Website\View;

use Filament\Forms\Components\Textarea;

class HtmlWidget extends BaseWidget
{
    public static function getType(): string
    {
        return 'html';
    }

    public static function getLabel(): string
    {
        return __('layup::widgets.labels.html');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-code-bracket';
    }

    public static function getCategory(): string
    {
        return 'advanced';
    }

    public static function getContentFormSchema(): array
    {
        return [
            Textarea::make('content')
                ->label(__('layup::widgets.html.html'))
                ->rows(10)
                ->columnSpanFull(),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'content' => '',
        ];
    }

    public static function getPreview(array $data): string
    {
        $html = $data['content'] ?? '';
        if (! $html) {
            return '(empty)';
        }
        $text = strip_tags((string) $html);
        $text = trim((string) preg_replace('/\s+/', ' ', $text));

        return mb_strlen($text) > 50 ? '< > ' . mb_substr($text, 0, 50) . '…' : '< > ' . $text;
    }
}
