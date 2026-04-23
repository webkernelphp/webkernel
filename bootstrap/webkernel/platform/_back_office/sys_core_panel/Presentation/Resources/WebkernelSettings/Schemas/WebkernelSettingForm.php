<?php

namespace Webkernel\BackOffice\System\Presentation\Resources\WebkernelSettings\Schemas;

use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Webkernel\BackOffice\System\Models\WebkernelSetting;
use Webkernel\BackOffice\System\Models\WebkernelSettingCategory;

class WebkernelSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        $categories = WebkernelSettingCategory::query()
            ->orderBy('sort_order')
            ->get();

        return $schema->components([
            Tabs::make('Settings')
                ->tabs(
                    $categories->map(fn ($category) =>
                        Tabs\Tab::make($category->label)
                            ->icon($category->icon)
                            ->schema([
                                Grid::make(4)->schema(
                                    self::getFieldsForCategory($category->key)
                                )
                            ])
                    )->toArray()
                )
        ]);
    }

    private static function getFieldsForCategory(string $category): array
    {
        return WebkernelSetting::forCategory($category)->get()
            ->map(fn ($setting) => self::buildField($setting))
            ->toArray();
    }

    private static function buildField(WebkernelSetting $setting)
    {
        $name = "settings.{$setting->key}";

        $field = match ($setting->type) {
            'password' => TextInput::make($name)->password()->revealable(),
            'boolean'  => Toggle::make($name),
            'integer'  => TextInput::make($name)->numeric(),
            'select'   => Select::make($name)->options(self::options($setting)),
            'textarea' => Textarea::make($name)->rows(2)->columnSpanFull(),
            default    => TextInput::make($name),
        };

        // CONDITIONAL DISPLAY (depends_on)
        if ($setting->meta_json['depends_on'] ?? false) {
            $dep = $setting->meta_json['depends_on'];

            $field->visible(fn ($get) =>
                $get("settings.{$dep['key']}") == $dep['value']
            );
        }

        return $field
            ->label($setting->label)
            ->helperText($setting->description)
            ->hint("v{$setting->introduced_in_version}");
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
