<?php declare(strict_types=1);

namespace Webkernel\CP\System\Presentation\Resources\AuditLog;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;
use Webkernel\Base\Audit\Models\AuditLog;
use Webkernel\CP\System\Presentation\Resources\AuditLog\Pages\ListAuditLogs;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static string|UnitEnum|null   $navigationGroup = 'Users & Permissions';
    protected static ?int                   $navigationSort  = 10;
    protected static ?string                $slug            = 'audit-log';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime()
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('actor.name')
                    ->label('Actor')
                    ->placeholder('(system)')
                    ->searchable(),

                \Filament\Tables\Columns\TextColumn::make('action')
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                \Filament\Tables\Columns\TextColumn::make('resource_type')
                    ->label('Resource')
                    ->searchable(),

                \Filament\Tables\Columns\TextColumn::make('resource_id')
                    ->label('ID')
                    ->limit(12),

                \Filament\Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP'),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('resource_type')
                    ->label('Resource Type')
                    ->options(
                        AuditLog::query()
                            ->whereNotNull('resource_type')
                            ->distinct()
                            ->pluck('resource_type', 'resource_type')
                            ->toArray()
                    ),
            ])
            ->actions([])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
        ];
    }
}
