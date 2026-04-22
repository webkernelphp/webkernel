<?php

declare(strict_types=1);

namespace Webkernel\BackOffice\System\Presentation\Resources\DatabaseConnections\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Webkernel\Panel\DatabaseConnectionsDataSource;
use Webkernel\Panel\Support\DatabaseConnectionRepository;

class DatabaseConnectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Connection')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

                TextColumn::make('label')
                    ->label('Label')
                    ->searchable()
                    ->default('—'),

                TextColumn::make('driver')
                    ->label('Driver')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'mysql'   => 'success',
                        'pgsql'   => 'info',
                        'sqlite'  => 'gray',
                        'sqlsrv'  => 'warning',
                        'mongodb' => 'primary',
                        default   => 'gray',
                    }),

                TextColumn::make('host')
                    ->label('Host')
                    ->toggleable()
                    ->default('—'),

                TextColumn::make('database')
                    ->label('Database')
                    ->toggleable()
                    ->default('—'),

                TextColumn::make('username')
                    ->label('User')
                    ->toggleable()
                    ->default('—'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                IconColumn::make('has_password')
                    ->label('Password')
                    ->state(fn (DatabaseConnectionsDataSource $r): bool => ! empty($r->password) || isset(((array) $r->env_map)['password']))
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('success')
                    ->falseColor('danger'),

                IconColumn::make('env_backed')
                    ->label('ENV')
                    ->state(fn (DatabaseConnectionsDataSource $r): bool => ! empty((array) $r->env_map))
                    ->boolean()
                    ->trueIcon('heroicon-o-variable')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('info')
                    ->falseColor('gray'),

                IconColumn::make('locked')
                    ->label('Locked')
                    ->state(fn (DatabaseConnectionsDataSource $r): bool => ! empty((array) $r->locked_fields))
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('warning')
                    ->falseColor('success'),
            ])

            ->filters([
                SelectFilter::make('driver')
                    ->options([
                        'mysql'   => 'MySQL / MariaDB',
                        'pgsql'   => 'PostgreSQL',
                        'sqlite'  => 'SQLite',
                        'sqlsrv'  => 'SQL Server',
                        'mongodb' => 'MongoDB',
                    ]),

                TernaryFilter::make('is_active')->label('Active'),

                TernaryFilter::make('env_backed')
                    ->label('ENV backed')
                    ->queries(
                        true: fn ($q) => $q->whereNotNull('env_map'),
                        false: fn ($q) => $q->whereNull('env_map'),
                    ),
            ])

            ->recordActions([
                EditAction::make(),

                Action::make('toggleActive')
                    ->label(fn (DatabaseConnectionsDataSource $r): string => (bool) $r->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn (DatabaseConnectionsDataSource $r): string => (bool) $r->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn (DatabaseConnectionsDataSource $r): string => (bool) $r->is_active ? 'danger' : 'success')
                    ->action(fn (DatabaseConnectionsDataSource $r) =>
                        DatabaseConnectionRepository::patch(
                            $r->name,
                            ['is_active' => (int) ! (bool) $r->is_active]
                        )
                    ),

                Action::make('testConnection')
                    ->label('Test')
                    ->icon('heroicon-o-signal')
                    ->color('info')
                    ->action(function (DatabaseConnectionsDataSource $r): void {
                        try {
                            DatabaseConnectionRepository::test($r->name);

                            \Filament\Notifications\Notification::make()
                                ->title('Connection successful')
                                ->success()
                                ->send();

                        } catch (\Throwable $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Connection failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Test connection?')
                    ->modalDescription('Attempts a live connection using current configuration.'),

                Action::make('duplicate')
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(fn (DatabaseConnectionsDataSource $r) =>
                        DatabaseConnectionRepository::duplicate($r->name)
                    ),

                Action::make('exportEnv')
                    ->label('Export to .env')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('warning')
                    ->visible(fn (DatabaseConnectionsDataSource $r): bool => empty((array) $r->env_map))
                    ->requiresConfirmation()
                    ->modalHeading('Export to environment?')
                    ->modalDescription('Moves sensitive values to .env and locks corresponding fields.')
                    ->action(fn (DatabaseConnectionsDataSource $r) =>
                        DatabaseConnectionRepository::exportToEnv($r->name)
                    ),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->using(function (array $records): void {
                            foreach ($records as $record) {
                                DatabaseConnectionRepository::remove($record->name);
                            }

                            DatabaseConnectionRepository::invalidateCache();
                        }),
                ]),
            ])

            ->striped()
            ->defaultSort('name')
            ->emptyStateHeading('No database connections')
            ->emptyStateDescription('Create your first connection. ENV-backed or config-based connections will appear automatically.')
            ->emptyStateIcon('heroicon-o-circle-stack');
    }
}
