<?php declare(strict_types=1);

namespace Webkernel\CP\Businesses\Presentation\Resources\Domains;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;
use Webkernel\Base\Domains\Models\Domain;
use Webkernel\CP\Businesses\Presentation\Resources\Domains\Pages\CreateBusinessDomain;
use Webkernel\CP\Businesses\Presentation\Resources\Domains\Pages\EditBusinessDomain;
use Webkernel\CP\Businesses\Presentation\Resources\Domains\Pages\ListBusinessDomains;

class BusinessDomainResource extends Resource
{
    protected static ?string $model = Domain::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';
    protected static string|UnitEnum|null   $navigationGroup = 'Configuration';
    protected static ?int                   $navigationSort  = 2;
    protected static ?string                $slug            = 'business-domains';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $businessId = request()->attributes->get('business_id');

        return parent::getEloquentQuery()
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Schemas\Components\Grid::make(2)->schema([
                \Filament\Forms\Components\TextInput::make('domain')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('custom-domain.com'),

                \Filament\Forms\Components\Select::make('panel_type')
                    ->options([
                        'business' => 'Business Panel',
                        'module'   => 'Module Panel',
                    ])
                    ->default('business')
                    ->required()
                    ->reactive(),

                \Filament\Forms\Components\Select::make('module_id')
                    ->relationship('module', 'name')
                    ->searchable()
                    ->visible(fn ($get) => $get('panel_type') === 'module'),

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

                \Filament\Tables\Columns\BadgeColumn::make('panel_type')
                    ->colors([
                        'info'    => 'business',
                        'warning' => 'module',
                    ]),

                \Filament\Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                \Filament\Tables\Columns\TextColumn::make('ssl_expires_at')
                    ->label('SSL Expires')
                    ->dateTime(),
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
            'index'  => ListBusinessDomains::route('/'),
            'create' => CreateBusinessDomain::route('/create'),
            'edit'   => EditBusinessDomain::route('/{record}/edit'),
        ];
    }
}
