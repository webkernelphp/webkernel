<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Presentation\Resources\BackgroundTasks\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Fieldset;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Webkernel\BackOffice\System\Models\WebkernelBackgroundTask;
use Webkernel\BackOffice\System\Presentation\Resources\BackgroundTasks\BackgroundTasksResource;
use Webkernel\Traits\HasBackgroundTasks;

class ListBackgroundTasks extends ListRecords
{
    use HasBackgroundTasks;

    protected static string $resource = BackgroundTasksResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->query(WebkernelBackgroundTask::query()->latest('created_at'))
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'secondary' => 'composer_update',
                        'secondary' => 'composer_update_all',
                        'info' => 'npm_update',
                        'info' => 'npm_update_all',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\BadgeColumn::make('status')
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-arrow-path' => 'running',
                        'heroicon-o-check-circle' => 'completed',
                        'heroicon-o-x-circle' => 'failed',
                        'heroicon-o-minus-circle' => 'cancelled',
                    ])
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'running',
                        'success' => 'completed',
                        'danger' => 'failed',
                        'gray' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('started_at')
                    ->label('Started')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->state(fn (WebkernelBackgroundTask $record): string => $record->getDurationFormatted()),

                Tables\Columns\TextColumn::make('output')
                    ->label('Output')
                    ->limit(50)
                    ->tooltip(fn (WebkernelBackgroundTask $record): ?string => $record->output ? str($record->output)->limit(200) : null),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'running' => 'Running',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'composer_update' => 'Composer Single',
                        'composer_update_all' => 'Composer All',
                        'npm_update' => 'NPM Single',
                        'npm_update_all' => 'NPM All',
                    ]),
            ])
            ->actions([
                Action::make('watch')
                    ->label('Watch')
                    ->icon('heroicon-o-terminal')
                    ->color('info')
                    ->slideOver()
                    ->modalWidth('7xl')
                    ->modalHeading(fn (WebkernelBackgroundTask $record): string => "Task: {$record->label}")
                    ->modalContent(fn (WebkernelBackgroundTask $record) => view('webkernel-system::actions.watch-task'))
                    ->disableForm(),

                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (WebkernelBackgroundTask $record): bool => $record->status === 'pending' || $record->status === 'running')
                    ->requiresConfirmation()
                    ->modalDescription('This will cancel the running task.')
                    ->action(fn (WebkernelBackgroundTask $record) => $record->markCancelled()),

                Action::make('retry')
                    ->label('Retry')
                    ->icon('heroicon-o-arrow-clockwise')
                    ->color('warning')
                    ->visible(fn (WebkernelBackgroundTask $record): bool => $record->status === 'failed' || $record->status === 'cancelled')
                    ->requiresConfirmation()
                    ->modalDescription('This will re-queue the task.')
                    ->action(fn (WebkernelBackgroundTask $record) => $this->retryTask($record)),

                Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (WebkernelBackgroundTask $record): bool => $record->status !== 'running' && $record->status !== 'pending')
                    ->requiresConfirmation()
                    ->modalDescription('This will permanently delete this task record.')
                    ->action(fn (WebkernelBackgroundTask $record) => $record->delete()),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('5s');
    }

    protected function retryTask(WebkernelBackgroundTask $record): void
    {
        $record->update(['status' => 'pending', 'output' => null, 'error' => null]);

        $payload = $record->payload?->toArray() ?? [];

        match ($record->type) {
            'composer_update' => $this->dispatchComposerPackageUpdate(
                (string) $record->id,
                $payload['package'] ?? '',
                $payload['version'] ?? ''
            ),
            'composer_update_all' => $this->dispatchAllComposerPackagesUpdate((string) $record->id),
            'npm_update' => $this->dispatchNpmPackageUpdate(
                (string) $record->id,
                $payload['package'] ?? '',
                $payload['version'] ?? ''
            ),
            'npm_update_all' => $this->dispatchAllNpmPackagesUpdate((string) $record->id),
            default => null,
        };
    }
}
