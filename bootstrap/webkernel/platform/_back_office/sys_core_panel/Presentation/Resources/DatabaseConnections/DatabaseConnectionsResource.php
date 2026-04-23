<?php
declare(strict_types=1);

namespace Webkernel\BackOffice\System\Presentation\Resources\DatabaseConnections;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Webkernel\BackOffice\System\Presentation\Resources\DatabaseConnections\Pages\CreateDatabaseConnection;
use Webkernel\BackOffice\System\Presentation\Resources\DatabaseConnections\Pages\EditDatabaseConnection;
use Webkernel\BackOffice\System\Presentation\Resources\DatabaseConnections\Pages\ListDatabaseConnections;
use Webkernel\BackOffice\System\Presentation\Resources\DatabaseConnections\Schemas\DatabaseConnectionForm;
use Webkernel\BackOffice\System\Presentation\Resources\DatabaseConnections\Tables\DatabaseConnectionsTable;
use Webkernel\Panel\DatabaseConnectionsDataSource;

class DatabaseConnectionsResource extends Resource
{
    protected static ?string $model = DatabaseConnectionsDataSource::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?string $navigationLabel = 'Database Connections';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'database-connections';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return DatabaseConnectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DatabaseConnectionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListDatabaseConnections::route('/'),
            'create' => CreateDatabaseConnection::route('/create'),
            'edit'   => EditDatabaseConnection::route('/{record}/edit'),
        ];
    }
}
