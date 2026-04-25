<?php declare(strict_types=1);

namespace Webkernel\CP\System\Presentation\Resources\DbConnections;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;
use Webkernel\Databases\Models\DbConnection;
use Webkernel\CP\System\Presentation\Resources\DbConnections\Pages\CreateDbConnection;
use Webkernel\CP\System\Presentation\Resources\DbConnections\Pages\EditDbConnection;
use Webkernel\CP\System\Presentation\Resources\DbConnections\Pages\ListDbConnections;

class DbConnectionResource extends Resource
{
    protected static ?string $model = DbConnection::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';
    protected static string|UnitEnum|null   $navigationGroup = 'Infrastructure';
    protected static ?int                   $navigationSort  = 2;
    protected static ?string                $slug            = 'db-connections';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Schemas\Components\Grid::make(2)->schema([
                \Filament\Forms\Components\Select::make('business_id')
                    ->relationship('business', 'name')
                    ->searchable()
                    ->required(),

                \Filament\Forms\Components\Select::make('module_id')
                    ->relationship('module', 'name')
                    ->searchable()
                    ->nullable()
                    ->helperText('Leave empty for the business-default connection.'),

                \Filament\Forms\Components\Select::make('driver')
                    ->options([
                        'mysql'  => 'MySQL',
                        'pgsql'  => 'PostgreSQL',
                        'sqlite' => 'SQLite',
                    ])
                    ->required()
                    ->reactive(),

                \Filament\Forms\Components\TextInput::make('host')
                    ->placeholder('127.0.0.1')
                    ->visible(fn ($get) => $get('driver') !== 'sqlite'),

                \Filament\Forms\Components\TextInput::make('port')
                    ->numeric()
                    ->visible(fn ($get) => $get('driver') !== 'sqlite'),

                \Filament\Forms\Components\TextInput::make('database')
                    ->required()
                    ->helperText('Database name, or file path for SQLite.'),

                \Filament\Forms\Components\TextInput::make('username')
                    ->visible(fn ($get) => $get('driver') !== 'sqlite'),

                \Filament\Forms\Components\TextInput::make('password_encrypted')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    ->visible(fn ($get) => $get('driver') !== 'sqlite')
                    ->helperText('Stored encrypted with APP_KEY.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('business.name')
                    ->label('Business')
                    ->searchable()
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('module.name')
                    ->label('Module')
                    ->placeholder('(business default)')
                    ->searchable(),

                \Filament\Tables\Columns\BadgeColumn::make('driver')
                    ->colors([
                        'info'    => 'mysql',
                        'primary' => 'pgsql',
                        'gray'    => 'sqlite',
                    ]),

                \Filament\Tables\Columns\TextColumn::make('host')
                    ->placeholder('—'),

                \Filament\Tables\Columns\TextColumn::make('database'),

                \Filament\Tables\Columns\IconColumn::make('verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->state(fn (DbConnection $r) => $r->verified_at !== null),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('driver')
                    ->options([
                        'mysql'  => 'MySQL',
                        'pgsql'  => 'PostgreSQL',
                        'sqlite' => 'SQLite',
                    ]),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->defaultSort('business_id');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListDbConnections::route('/'),
            'create' => CreateDbConnection::route('/create'),
            'edit'   => EditDbConnection::route('/{record}/edit'),
        ];
    }
}
