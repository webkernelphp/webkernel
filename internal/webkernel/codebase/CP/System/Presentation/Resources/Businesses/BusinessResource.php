<?php declare(strict_types=1);

namespace Webkernel\CP\System\Presentation\Resources\Businesses;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;
use Webkernel\Base\Businesses\Models\Business;
use Webkernel\CP\System\Presentation\Resources\Businesses\Pages\CreateBusiness;
use Webkernel\CP\System\Presentation\Resources\Businesses\Pages\EditBusiness;
use Webkernel\CP\System\Presentation\Resources\Businesses\Pages\ListBusinesses;

class BusinessResource extends Resource
{
    protected static ?string $model = Business::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';
    protected static string|UnitEnum|null   $navigationGroup = 'Core Instance';
    protected static ?int                   $navigationSort  = 1;
    protected static ?string                $slug            = 'businesses';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Schemas\Components\Grid::make(2)->schema([
                \Filament\Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(120),

                \Filament\Forms\Components\TextInput::make('slug')
                    ->maxLength(63)
                    ->helperText('Auto-generated from name if left blank.'),

                \Filament\Forms\Components\TextInput::make('admin_email')
                    ->email()
                    ->required()
                    ->maxLength(255),

                \Filament\Forms\Components\Select::make('status')
                    ->options([
                        'pending'   => 'Pending',
                        'active'    => 'Active',
                        'suspended' => 'Suspended',
                    ])
                    ->default('pending')
                    ->required(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('slug')
                    ->searchable(),

                \Filament\Tables\Columns\TextColumn::make('admin_email')
                    ->searchable(),

                \Filament\Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'active',
                        'danger'  => 'suspended',
                    ]),

                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'   => 'Pending',
                        'active'    => 'Active',
                        'suspended' => 'Suspended',
                    ]),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListBusinesses::route('/'),
            'create' => CreateBusiness::route('/create'),
            'edit'   => EditBusiness::route('/{record}/edit'),
        ];
    }
}
