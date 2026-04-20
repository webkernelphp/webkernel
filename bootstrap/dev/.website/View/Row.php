<?php

declare(strict_types=1);

namespace Webkernel\Builders\Website\View;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Illuminate\Contracts\View\View;

class Row extends BaseView
{
    /**
     * @param  array<Column>  $columns
     */
    public static function make(array $data = [], array $children = []): static
    {
        return new static($data, $children);
    }

    /**
     * Content tab: flex direction, justify, align, gap, wrap.
     */
    public static function getContentFormSchema(): array
    {
        return [
            Select::make('direction')
                ->label(__('layup::widgets.row.direction'))
                ->options([
                    'row' => __('layup::widgets.row.row_horizontal'),
                    'column' => __('layup::widgets.row.column_vertical'),
                    'row-reverse' => __('layup::widgets.row.row_reverse'),
                    'column-reverse' => __('layup::widgets.row.column_reverse'),
                ])
                ->default('row'),
            Select::make('justify')
                ->label(__('layup::widgets.row.justify_content'))
                ->options([
                    'start' => __('layup::widgets.row.start'),
                    'center' => __('layup::widgets.row.center'),
                    'end' => __('layup::widgets.row.end'),
                    'between' => __('layup::widgets.row.space_between'),
                    'around' => __('layup::widgets.row.space_around'),
                    'evenly' => __('layup::widgets.row.space_evenly'),
                ])
                ->default('start'),
            Select::make('align')
                ->label(__('layup::widgets.row.align_items'))
                ->options([
                    'start' => __('layup::widgets.row.start'),
                    'center' => __('layup::widgets.row.center'),
                    'end' => __('layup::widgets.row.end'),
                    'stretch' => __('layup::widgets.row.stretch'),
                    'baseline' => __('layup::widgets.row.baseline'),
                ])
                ->default('stretch'),
            Select::make('wrap')
                ->label(__('layup::widgets.row.wrap'))
                ->options([
                    'nowrap' => __('layup::widgets.row.no_wrap'),
                    'wrap' => __('layup::widgets.row.wrap_option'),
                    'wrap-reverse' => __('layup::widgets.row.wrap_reverse'),
                ])
                ->default('wrap'),
            Toggle::make('full_width')
                ->label(__('layup::widgets.row.full_width'))
                ->helperText(__('layup::widgets.row.full_width_helper'))
                ->default(false),
        ];
    }

    public function render(): View
    {
        return view('layup::components.row', [
            'children' => $this->children,
            'data' => $this->data,
        ]);
    }
}
