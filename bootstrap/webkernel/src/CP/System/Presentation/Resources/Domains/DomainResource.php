<?php declare(strict_types=1);

namespace Webkernel\CP\System\Presentation\Resources\Domains;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;
use Webkernel\Domains\Models\Domain;
use Webkernel\CP\System\Presentation\Resources\Domains\Pages\CreateDomain;
use Webkernel\CP\System\Presentation\Resources\Domains\Pages\EditDomain;
use Webkernel\CP\System\Presentation\Resources\Domains\Pages\ListDomains;

class DomainResource extends Resource
{
    protected static ?string $model = Domain::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';
    protected static string|UnitEnum|null   $navigationGroup = 'Infrastructure';
    protected static ?int                   $navigationSort  = 1;
    protected static ?string                $slug            = 'domains';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Schemas\Components\Grid::make(2)->schema([
                \Filament\Forms\Components\TextInput::make('domain')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('sales.acmecorp.io'),

                \Filament\Forms\Components\Select::make('business_id')
                    ->relationship('business', 'name')
                    ->searchable()
                    ->required(),

                \Filament\Forms\Components\Select::make('panel_type')
                    ->options([
                        'system'   => 'System Panel',
                        'business' => 'Business Panel',
                        'module'   => 'Module Panel',
                    ])
                    ->required()
                    ->reactive(),

                \Filament\Forms\Components\Select::make('module_id')
                    ->relationship('module', 'name')
                    ->searchable()
                    ->visible(fn ($get) => $get('panel_type') === 'module'),

                \Filament\Forms\Components\Toggle::make('is_primary')
                    ->label('Primary domain'),

                \Filament\Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('domain')
                    ->searchable()
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('business.name')
                    ->label('Business')
                    ->searchable(),

                \Filament\Tables\Columns\BadgeColumn::make('panel_type')
                    ->colors([
                        'primary' => 'system',
                        'info'    => 'business',
                        'warning' => 'module',
                    ]),

                \Filament\Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                \Filament\Tables\Columns\IconColumn::make('is_primary')
                    ->boolean()
                    ->label('Primary'),

                \Filament\Tables\Columns\TextColumn::make('ssl_expires_at')
                    ->label('SSL Expires')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('panel_type')
                    ->options([
                        'system'   => 'System',
                        'business' => 'Business',
                        'module'   => 'Module',
                    ]),

                \Filament\Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->defaultSort('domain');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListDomains::route('/'),
            'create' => CreateDomain::route('/create'),
            'edit'   => EditDomain::route('/{record}/edit'),
        ];
    }
}
