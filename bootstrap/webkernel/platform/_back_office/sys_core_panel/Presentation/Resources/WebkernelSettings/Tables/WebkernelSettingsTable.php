<?php

namespace Webkernel\BackOffice\System\Presentation\Resources\WebkernelSettings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Schemas\Components\Tabs;
use Webkernel\BackOffice\System\Models\WebkernelSettingCategory;
class WebkernelSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->description),

                TextColumn::make('key')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Setting key copied')
                    ->fontFamily('mono')
                    ->color('primary'),

                TextColumn::make('category')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        return WebkernelSettingCategory::find($state)?->label ?? $state;
                    }),

                TextColumn::make('type')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('value')
                    ->searchable()
                    ->limit(50)
                    // Mask the value if the type is a password
                    ->formatStateUsing(fn ($state, $record) => $record->type === 'password' ? '••••••••' : $state),

                TextColumn::make('introduced_in_version')
                    ->label('Version')
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(
                        WebkernelSettingCategory::pluck('label', 'key')->toArray()
                    ),

                SelectFilter::make('type')
                    ->options([
                        'string' => 'String',
                        'password' => 'Password',
                        'boolean' => 'Boolean',
                        'integer' => 'Integer',
                        'select' => 'Select',
                        'textarea' => 'Textarea',
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->modal()
                    ->slideOver(false),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
