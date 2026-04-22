<?php
declare(strict_types=1);

namespace Webkernel\BackOffice\System\Presentation\Resources\DatabaseConnections\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Webkernel\BackOffice\System\Presentation\Resources\DatabaseConnections\DatabaseConnectionsResource;
use Webkernel\Panel\DatabaseConnectionsDataSource;
use Webkernel\Panel\DTO\DatabaseConnectionDTO;
use Webkernel\Panel\Support\DatabaseConnectionRepository;

class EditDatabaseConnection extends EditRecord
{
    protected static string $resource = DatabaseConnectionsResource::class;

    /**
     * Ensure rows are populated even when this page is accessed directly by URL.
     */
    public function mount(int|string $record): void
    {
        DatabaseConnectionsDataSource::refreshRows();
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export to FSEngine')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->visible(fn (DatabaseConnectionsDataSource $record): bool => $record->source === 'static')
                ->requiresConfirmation()
                ->modalHeading('Export connection to FSEngine?')
                ->modalDescription(
                    'The connection config will be copied from config/database.php into the FSEngine store. '
                    . 'You can then edit it here. The original file is not modified. '
                    . 'Passwords are never copied — env-backed fields remain env-backed.'
                )
                ->action(function (DatabaseConnectionsDataSource $record): void {
                    DatabaseConnectionRepository::export($record->name);
                    Notification::make()
                        ->title('Connection exported to FSEngine.')
                        ->success()
                        ->send();
                    DatabaseConnectionsDataSource::refreshRows();
                    $this->redirect(
                        $this->getResource()::getUrl('edit', ['record' => $record->name])
                    );
                }),

            DeleteAction::make()
                ->visible(fn (DatabaseConnectionsDataSource $record): bool => $record->source === 'dynamic')
                ->using(function (DatabaseConnectionsDataSource $record): void {
                    DatabaseConnectionRepository::remove($record->name);
                    DatabaseConnectionRepository::invalidateCache();
                    DatabaseConnectionsDataSource::refreshRows();
                }),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function handleRecordUpdate(Model $record, array $data): DatabaseConnectionsDataSource
    {
        /** @var DatabaseConnectionsDataSource $record */
        $existing = DatabaseConnectionRepository::find($record->name);

        if ($existing === null) {
            $existing = DatabaseConnectionRepository::export($record->name);
        }

        $base   = $existing?->toArray() ?? [];
        $merged = array_merge($base, $data, ['name' => $record->name]);

        foreach ($existing?->lockedFields ?? [] as $locked) {
            unset($merged[$locked]);
            if (isset($base[$locked])) {
                $merged[$locked] = $base[$locked];
            }
        }

        $dto = DatabaseConnectionDTO::fromArray($merged);

        // Handle env-var writes when the user explicitly submitted values for env-backed fields.
        $envWrites = $data['env_writes'] ?? [];
        if (is_array($envWrites)) {
            foreach ($envWrites as $field => $newValue) {
                if (! is_string($newValue) || $newValue === '') {
                    continue;
                }
                $envKey = $existing?->envMap[$field] ?? null;
                if ($envKey === null) {
                    continue;
                }
                try {
                    DatabaseConnectionRepository::writeEnv($envKey, $newValue);
                } catch (\RuntimeException $e) {
                    Notification::make()
                        ->title("Could not write {$envKey} to .env: " . $e->getMessage())
                        ->warning()
                        ->send();
                }
            }
        }

        DatabaseConnectionRepository::save($dto);
        DatabaseConnectionRepository::invalidateCache();

        DatabaseConnectionsDataSource::refreshRows();

        return DatabaseConnectionsDataSource::find($record->name)
            ?? $record->forceFill($dto->toArray());
    }
}
