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

class ListBackgroundTasks extends ListRecords
{
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
                Action::make('details')
                    ->label('Details')
                    ->icon('heroicon-o-terminal')
                    ->color('info')
                    ->visible(fn (WebkernelBackgroundTask $record): bool => !empty($record->output) || !empty($record->error) || $record->status === 'running')
                    ->modalHeading(fn (WebkernelBackgroundTask $record): string => "Task Details: {$record->label}")
                    ->slideOver()
                    ->modalWidth('7xl')
                    ->fillForm(fn (WebkernelBackgroundTask $record) => [
                        'status' => $record->status,
                        'type' => $record->type,
                        'duration' => $record->getDurationFormatted(),
                        'started_at' => $record->started_at?->format('M d, Y H:i:s'),
                        'completed_at' => $record->completed_at?->format('M d, Y H:i:s'),
                        'payload' => $record->payload ? json_encode($record->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : null,
                        'output' => $record->output,
                        'error' => $record->error,
                    ])
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'running' => 'info',
                                'completed' => 'success',
                                'failed' => 'danger',
                                'cancelled' => 'gray',
                                default => 'gray',
                            }),
                        TextEntry::make('type')
                            ->label('Task Type')
                            ->formatStateUsing(fn (string $state) => str($state)->replace('_', ' ')->title()),
                        TextEntry::make('duration')
                            ->label('Duration'),
                        TextEntry::make('started_at')
                            ->label('Started'),
                        TextEntry::make('completed_at')
                            ->label('Completed'),
                        Fieldset::make('Payload')
                            ->visible(fn (WebkernelBackgroundTask $record) => $record->payload !== null)
                            ->schema([
                                TextEntry::make('payload')
                                    ->copyable()
                                    ->copyableState(fn (string $state): string => $state),
                            ]),
                        Fieldset::make('Output')
                            ->visible(fn (WebkernelBackgroundTask $record) => !empty($record->output))
                            ->schema([
                                TextEntry::make('output')
                                    ->copyable()
                                    ->formatStateUsing(fn (string $state): string => $state),
                            ]),
                        Section::make('Error')
                            ->visible(fn (WebkernelBackgroundTask $record) => !empty($record->error))
                            ->schema([
                                TextEntry::make('error')
                                    ->copyable()
                                    ->color('danger')
                                    ->formatStateUsing(fn (string $state): string => $state),
                            ]),
                    ]),

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

        $jobClass = match ($record->type) {
            'composer_update' => \Webkernel\BackOffice\System\Jobs\UpdateComposerPackageJob::class,
            'composer_update_all' => \Webkernel\BackOffice\System\Jobs\UpdateAllComposerPackagesJob::class,
            'npm_update' => \Webkernel\BackOffice\System\Jobs\UpdateNpmPackageJob::class,
            'npm_update_all' => \Webkernel\BackOffice\System\Jobs\UpdateAllNpmPackagesJob::class,
            default => null,
        };

        if (!$jobClass) {
            return;
        }

        $payload = $record->payload?->toArray() ?? [];

        if ($record->type === 'composer_update' || $record->type === 'npm_update') {
            $job = new $jobClass($record->id, $payload['package'] ?? '', $payload['version'] ?? '');
        } else {
            $job = new $jobClass($record->id);
        }

        dispatch($job);
    }
}
