<?php

namespace Webkernel\BackOffice\System\Presentation\Resources\WebkernelSettings;

use Webkernel\BackOffice\System\Models\WebkernelSetting;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Webkernel\BackOffice\System\Presentation\Resources\WebkernelSettings\Pages\CreateWebkernelSetting;
use Webkernel\BackOffice\System\Presentation\Resources\WebkernelSettings\Pages\EditWebkernelSetting;
use Webkernel\BackOffice\System\Presentation\Resources\WebkernelSettings\Pages\ListWebkernelSettings;
use Webkernel\BackOffice\System\Presentation\Resources\WebkernelSettings\Schemas\WebkernelSettingForm;
use Webkernel\BackOffice\System\Presentation\Resources\WebkernelSettings\Tables\WebkernelSettingsTable;

class WebkernelSettingResource extends Resource
{
    protected static ?string $model = WebkernelSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    public static function form(Schema $schema): Schema
    {
        return WebkernelSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WebkernelSettingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWebkernelSettings::route('/'),
            'create' => CreateWebkernelSetting::route('/create'),
            'edit' => EditWebkernelSetting::route('/{record}/edit'),
        ];
    }
}
