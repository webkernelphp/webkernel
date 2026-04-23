<?php
declare(strict_types=1);

namespace Webkernel\BackOffice\System\Presentation\Resources\PanelArrays;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Webkernel\BackOffice\System\Presentation\Resources\PanelArrays\Pages\CreatePanelArrays;
use Webkernel\BackOffice\System\Presentation\Resources\PanelArrays\Pages\EditPanelArrays;
use Webkernel\BackOffice\System\Presentation\Resources\PanelArrays\Pages\ListPanelArrays;
use Webkernel\BackOffice\System\Presentation\Resources\PanelArrays\Schemas\PanelArraysForm;
use Webkernel\BackOffice\System\Presentation\Resources\PanelArrays\Tables\PanelArraysTable;
use Webkernel\Panel\PanelArraysDataSource;

class PanelArraysResource extends Resource
{
    protected static ?string $model = PanelArraysDataSource::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Panels';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $slug = 'panels';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return PanelArraysForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PanelArraysTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPanelArrays::route('/'),
            'create' => CreatePanelArrays::route('/create'),
            'edit'   => EditPanelArrays::route('/{record}/edit'),
        ];
    }
}
