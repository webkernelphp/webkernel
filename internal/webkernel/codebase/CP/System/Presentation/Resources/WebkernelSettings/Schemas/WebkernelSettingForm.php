<?php

namespace Webkernel\CP\System\Presentation\Resources\WebkernelSettings\Schemas;

use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Webkernel\CP\System\Models\WebkernelSetting;
use Webkernel\CP\System\Models\WebkernelSettingCategory;

class WebkernelSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        // Check if we're creating or editing
        $isCreating = request()->route()->getName() === 'filament.system.resources.webkernel-settings.create';

        if ($isCreating) {
            return self::createForm($schema);
        }

        return $schema->components([
            self::buildValueField(),
        ]);
    }

    private static function createForm(Schema $schema): Schema
    {
        return $schema->components([
                Section::make('Basic Info')->schema([
                    Select::make('category')
                        ->label('Category')
                        ->options(WebkernelSettingCategory::pluck('label', 'key')->toArray())
                        ->required()
                        ->native(false)
                        ->searchable(),

                    TextInput::make('key')
                        ->label('Setting Key')
                        ->placeholder('e.g., app_name')
                        ->required()
                        ->helperText('Machine-readable key (lowercase, underscores)'),

                    TextInput::make('label')
                        ->label('Display Label')
                        ->placeholder('e.g., Application Name')
                        ->required(),

                    Textarea::make('description')
                        ->label('Description')
                        ->placeholder('What this setting does...')
                        ->rows(2)
                        ->columnSpanFull(),
                ])->columnSpan(1),

                Section::make('Configuration')->schema([
                    Select::make('type')
                        ->label('Type')
                        ->options([
                            'text' => 'Text',
                            'password' => 'Password',
                            'boolean' => 'Boolean',
                            'integer' => 'Integer',
                            'select' => 'Select',
                            'textarea' => 'Textarea',
                            'json' => 'JSON',
                        ])
                        ->native(false)
                        ->required()
                        ->reactive(),

                    TextInput::make('default_value')
                        ->label('Default Value')
                        ->placeholder('Default value if not set'),

                    Textarea::make('options_json')
                        ->label('Options (JSON)')
                        ->visible(fn($get) => $get('type') === 'select')
                        ->placeholder('[{"value": "opt1", "label": "Option 1"}, ...]')
                        ->rows(3),

                    TextInput::make('enum_class')
                        ->label('Enum Class')
                        ->visible(fn($get) => $get('type') === 'enum')
                        ->placeholder('App\\Enums\\MyEnum'),
                ])->columnSpan(1),

                Section::make('Metadata')->schema([
                    Select::make('registry')
                        ->label('Registry')
                        ->options(['webkernel' => 'Webkernel', 'custom' => 'Custom'])
                        ->default('webkernel'),

                    TextInput::make('vendor')
                        ->label('Vendor')
                        ->placeholder('e.g., acme-corp'),

                    TextInput::make('module')
                        ->label('Module')
                        ->placeholder('e.g., billing'),

                    Toggle::make('is_sensitive')
                        ->label('Sensitive (encrypted)'),

                    Toggle::make('is_custom')
                        ->label('User-created'),
                ])->columnSpan(2)->columns(2),
        ]);
    }

    private static function buildValueField(): mixed
    {
        $record = request()->route('record');

        if (!$record) {
            return TextInput::make('value')->label('Value');
        }

        $baseField = match ($record->type) {
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

        $dependents = WebkernelSetting::where('depends_on_key', $record->key)
            ->where('category', $record->category)
            ->orderBy('sort_order')
            ->get();

        if ($dependents->isEmpty()) {
            return $baseField;
        }

        $dependentFields = [];
        foreach ($dependents as $dep) {
            $dependentFields[] = self::buildDependentField($dep, $record);
        }

        return Section::make('Group: ' . $record->label)
            ->description('Related settings')
            ->schema(array_merge([$baseField], $dependentFields))
            ->collapsible();
    }

    private static function buildDependentField(WebkernelSetting $dependent, WebkernelSetting $parent): mixed
    {
        $field = match ($dependent->type) {
            'password' => TextInput::make("_dependent_{$dependent->key}")
                ->label($dependent->label)
                ->password()
                ->revealable()
                ->hint($dependent->description)
                ->default($dependent->resolvedValue()),

            'boolean' => Toggle::make("_dependent_{$dependent->key}")
                ->label($dependent->label)
                ->hint($dependent->description)
                ->default($dependent->resolvedValue()),

            'integer' => TextInput::make("_dependent_{$dependent->key}")
                ->label($dependent->label)
                ->numeric()
                ->hint($dependent->description)
                ->default($dependent->resolvedValue()),

            'select' => Select::make("_dependent_{$dependent->key}")
                ->label($dependent->label)
                ->options(self::options($dependent))
                ->hint($dependent->description)
                ->default($dependent->resolvedValue()),

            'textarea' => Textarea::make("_dependent_{$dependent->key}")
                ->label($dependent->label)
                ->rows(3)
                ->hint($dependent->description)
                ->default($dependent->resolvedValue()),

            default => TextInput::make("_dependent_{$dependent->key}")
                ->label($dependent->label)
                ->hint($dependent->description)
                ->default($dependent->resolvedValue()),
        };

        return $field
            ->disabled()
            ->dehydrated(false)
            ->visible(fn($get) => $get('value') == $parent->depends_on_value || ($parent->type === 'boolean' && $get('value')));
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
