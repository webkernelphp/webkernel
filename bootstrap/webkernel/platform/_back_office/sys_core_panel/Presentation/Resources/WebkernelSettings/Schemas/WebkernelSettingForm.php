<?php

namespace Webkernel\BackOffice\System\Presentation\Resources\WebkernelSettings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Webkernel\BackOffice\System\Models\WebkernelSetting;

class WebkernelSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            self::buildValueField(),
        ]);
    }

    private static function buildValueField(): mixed
    {
        // Get the record being edited
        $record = request()->route('record');

        if (!$record) {
            return TextInput::make('value')->label('Value');
        }

        return match ($record->type) {
            'password' => TextInput::make('value')
                ->label($record->label)
                ->password()
                ->revealable()
                ->hint($record->description)
                ->helperText("Key: {$record->key} • v{$record->introduced_in_version}")
                ->required(),

            'boolean' => Toggle::make('value')
                ->label($record->label)
                ->hint($record->description)
                ->helperText("Key: {$record->key} • v{$record->introduced_in_version}"),

            'integer' => TextInput::make('value')
                ->label($record->label)
                ->numeric()
                ->hint($record->description)
                ->helperText("Key: {$record->key} • v{$record->introduced_in_version}")
                ->required(),

            'select' => Select::make('value')
                ->label($record->label)
                ->options(self::options($record))
                ->hint($record->description)
                ->helperText("Key: {$record->key} • v{$record->introduced_in_version}")
                ->required(),

            'textarea' => Textarea::make('value')
                ->label($record->label)
                ->rows(4)
                ->hint($record->description)
                ->helperText("Key: {$record->key} • v{$record->introduced_in_version}")
                ->columnSpanFull(),

            default => TextInput::make('value')
                ->label($record->label)
                ->hint($record->description)
                ->helperText("Key: {$record->key} • v{$record->introduced_in_version}")
                ->required(),
        };
    }

    private static function options(WebkernelSetting $setting): array
    {
        if ($setting->enum_class && enum_exists($setting->enum_class)) {
            return collect($setting->enum_class::cases())
                ->mapWithKeys(fn ($case) => [$case->value => $case->name])
                ->toArray();
        }

        return collect($setting->options_json ?? [])
            ->pluck('label', 'value')
            ->toArray();
    }
}
