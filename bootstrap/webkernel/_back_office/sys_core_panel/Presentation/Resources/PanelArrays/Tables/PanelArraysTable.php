<?php
declare(strict_types=1);

namespace Webkernel\BackOffice\System\Presentation\Resources\PanelArrays\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Webkernel\Panel\PanelArraysDataSource;
use Webkernel\Panel\Support\PanelConfigRepository;

class PanelArraysTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Panel')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

                TextColumn::make('brand_name')
                    ->label('Brand')
                    ->searchable()
                    ->default('—'),

                TextColumn::make('source')
                    ->label('Source')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'dynamic' => 'success',
                        'static'  => 'gray',
                        default   => 'gray',
                    }),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                IconColumn::make('auth')
                    ->label('Auth')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->trueColor('success')
                    ->falseColor('danger'),

                IconColumn::make('registration')
                    ->label('Reg.')
                    ->boolean()
                    ->trueIcon('heroicon-o-user-plus')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('info')
                    ->falseColor('gray'),

                IconColumn::make('spa')
                    ->label('SPA')
                    ->boolean()
                    ->trueIcon('heroicon-o-bolt')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('primary')
                    ->falseColor('gray'),

                IconColumn::make('tenant_enabled')
                    ->label('Tenant')
                    ->boolean()
                    ->trueIcon('heroicon-o-building-office')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                TextColumn::make('primary_color')
                    ->label('Color')
                    ->html()
                    ->formatStateUsing(fn (?string $state): string => empty($state)
                        ? '<span style="color:var(--color-text-secondary)">—</span>'
                        : '<span style="display:inline-flex;align-items:center;gap:6px;">'
                            . '<span style="width:13px;height:13px;border-radius:3px;background:' . e($state) . ';display:inline-block;border:0.5px solid rgba(0,0,0,.15);flex-shrink:0;"></span>'
                            . '<code style="font-size:12px;">' . e($state) . '</code>'
                            . '</span>'),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
                TernaryFilter::make('is_default')->label('Default'),
                TernaryFilter::make('auth')->label('Auth'),
                TernaryFilter::make('registration')->label('Registration'),
                TernaryFilter::make('spa')->label('SPA'),
                TernaryFilter::make('tenant_enabled')->label('Tenant'),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('setDefault')
                    ->label('Set as default')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(fn (PanelArraysDataSource $r): bool => ! (bool) $r->is_default && $r->source === 'dynamic')
                    ->requiresConfirmation()
                    ->modalHeading('Set as default panel?')
                    ->modalDescription('The default flag will be removed from all other dynamic panels. Restart workers to apply.')
                    ->action(function (PanelArraysDataSource $r): void {
                        PanelConfigRepository::setDefault($r->id);
                        PanelConfigRepository::invalidateCache();
                        PanelArraysDataSource::refreshRows();
                    }),

                Action::make('toggleActive')
                    ->label(fn (PanelArraysDataSource $r): string => (bool) $r->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn (PanelArraysDataSource $r): string => (bool) $r->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn (PanelArraysDataSource $r): string => (bool) $r->is_active ? 'danger' : 'success')
                    ->visible(fn (PanelArraysDataSource $r): bool => $r->source === 'dynamic')
                    ->action(function (PanelArraysDataSource $r): void {
                        PanelConfigRepository::patch($r->id, ['is_active' => (int) ! (bool) $r->is_active]);
                        PanelArraysDataSource::refreshRows();
                    }),

                Action::make('toggleAuth')
                    ->label(fn (PanelArraysDataSource $r): string => (bool) $r->auth ? 'Disable auth' : 'Enable auth')
                    ->icon(fn (PanelArraysDataSource $r): string => (bool) $r->auth ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                    ->color(fn (PanelArraysDataSource $r): string => (bool) $r->auth ? 'danger' : 'success')
                    ->visible(fn (PanelArraysDataSource $r): bool => $r->source === 'dynamic')
                    ->action(function (PanelArraysDataSource $r): void {
                        PanelConfigRepository::patch($r->id, ['auth' => (int) ! (bool) $r->auth]);
                        PanelArraysDataSource::refreshRows();
                    }),

                Action::make('toggleRegistration')
                    ->label(fn (PanelArraysDataSource $r): string => (bool) $r->registration ? 'Disable registration' : 'Enable registration')
                    ->icon(fn (PanelArraysDataSource $r): string => (bool) $r->registration ? 'heroicon-o-user-minus' : 'heroicon-o-user-plus')
                    ->color('info')
                    ->visible(fn (PanelArraysDataSource $r): bool => $r->source === 'dynamic')
                    ->action(function (PanelArraysDataSource $r): void {
                        PanelConfigRepository::patch($r->id, ['registration' => (int) ! (bool) $r->registration]);
                        PanelArraysDataSource::refreshRows();
                    }),

                Action::make('toggleSpa')
                    ->label(fn (PanelArraysDataSource $r): string => (bool) $r->spa ? 'Disable SPA' : 'Enable SPA')
                    ->icon('heroicon-o-bolt')
                    ->color('primary')
                    ->visible(fn (PanelArraysDataSource $r): bool => $r->source === 'dynamic')
                    ->action(function (PanelArraysDataSource $r): void {
                        PanelConfigRepository::patch($r->id, ['spa' => (int) ! (bool) $r->spa]);
                        PanelArraysDataSource::refreshRows();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->using(function (array $records): void {
                            foreach ($records as $record) {
                                if ($record->source === 'dynamic') {
                                    PanelConfigRepository::remove($record->id);
                                }
                            }
                            PanelConfigRepository::invalidateCache();
                            PanelArraysDataSource::refreshRows();
                        }),
                ]),
            ])
            ->striped()
            ->defaultSort('sort_order')
            ->emptyStateHeading('No panels defined')
            ->emptyStateDescription('Create your first panel using the button above. Static provider panels appear here automatically.')
            ->emptyStateIcon('heroicon-o-rectangle-stack');
    }
}
