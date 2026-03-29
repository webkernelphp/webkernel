<?php

declare(strict_types=1);

namespace Webkernel\Builders\Website\Forms\Components;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class SpacingPicker
{
    /**
     * Simple mode: single value applied to all sides.
     */
    public static function simple(string $name = 'padding', string $label = 'Padding'): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->numeric()
            ->nullable()
            ->suffix('px');
    }

    /**
     * Advanced mode: per-side inputs with unit selector.
     * Uses native Filament inputs in a box model layout.
     */
    public static function advanced(string $name = 'padding', string $label = 'Padding'): Section
    {
        return Section::make($label)
            ->schema([
                Select::make("{$name}.unit")
                    ->label(__('layup::widgets.shared.unit'))
                    ->options([
                        'px' => 'px',
                        'rem' => 'rem',
                        'em' => 'em',
                        '%' => '%',
                    ])
                    ->default('px')
                    ->selectablePlaceholder(false)
                    ->columnSpan(2),

                Grid::make(1)
                    ->schema([
                        TextInput::make("{$name}.top")
                            ->label(__('layup::widgets.shared.top'))
                            ->numeric()
                            ->nullable()
                            ->placeholder('0'),
                    ])
                    ->columnSpan(2)
                    ->extraAttributes(['class' => 'lyp-spacing-box-top']),

                Grid::make(3)
                    ->schema([
                        TextInput::make("{$name}.left")
                            ->label(__('layup::widgets.shared.left'))
                            ->numeric()
                            ->nullable()
                            ->placeholder('0'),
                        TextInput::make("{$name}.all")
                            ->label(strtoupper(substr($label, 0, 3)))
                            ->disabled()
                            ->placeholder('—')
                            ->extraInputAttributes(['class' => 'text-center']),
                        TextInput::make("{$name}.right")
                            ->label(__('layup::widgets.shared.right'))
                            ->numeric()
                            ->nullable()
                            ->placeholder('0'),
                    ])
                    ->columnSpan(2)
                    ->extraAttributes(['class' => 'lyp-spacing-box-middle']),

                Grid::make(1)
                    ->schema([
                        TextInput::make("{$name}.bottom")
                            ->label(__('layup::widgets.shared.bottom'))
                            ->numeric()
                            ->nullable()
                            ->placeholder('0'),
                    ])
                    ->columnSpan(2)
                    ->extraAttributes(['class' => 'lyp-spacing-box-bottom']),
            ])
            ->columns(2)
            ->collapsed()
            ->compact();
    }
}
