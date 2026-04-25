<?php declare(strict_types=1);

namespace Webkernel\CP\System\Presentation\Resources\Modules;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;
use Webkernel\Modules\Models\Module;
use Webkernel\CP\System\Presentation\Resources\Modules\Pages\CreateModule;
use Webkernel\CP\System\Presentation\Resources\Modules\Pages\EditModule;
use Webkernel\CP\System\Presentation\Resources\Modules\Pages\ListModules;

class ModuleResource extends Resource
{
    protected static ?string $model = Module::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-puzzle-piece';
    protected static string|UnitEnum|null   $navigationGroup = 'Modules & Extensions';
    protected static ?int                   $navigationSort  = 1;
    protected static ?string                $slug            = 'modules';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Schemas\Components\Grid::make(2)->schema([
                \Filament\Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(120),

                \Filament\Forms\Components\TextInput::make('vendor')
                    ->required()
                    ->maxLength(60),

                \Filament\Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(63),

                \Filament\Forms\Components\TextInput::make('version')
                    ->required()
                    ->maxLength(30)
                    ->default('1.0.0'),

                \Filament\Forms\Components\Select::make('status')
                    ->options([
                        'enabled'    => 'Enabled',
                        'disabled'   => 'Disabled',
                        'installing' => 'Installing',
                        'error'      => 'Error',
                    ])
                    ->default('enabled')
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

                \Filament\Tables\Columns\TextColumn::make('vendor')
                    ->searchable(),

                \Filament\Tables\Columns\TextColumn::make('slug')
                    ->searchable(),

                \Filament\Tables\Columns\TextColumn::make('version'),

                \Filament\Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'enabled',
                        'gray'    => 'disabled',
                        'warning' => 'installing',
                        'danger'  => 'error',
                    ]),

                \Filament\Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'enabled'    => 'Enabled',
                        'disabled'   => 'Disabled',
                        'installing' => 'Installing',
                        'error'      => 'Error',
                    ]),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListModules::route('/'),
            'create' => CreateModule::route('/create'),
            'edit'   => EditModule::route('/{record}/edit'),
        ];
    }
}
