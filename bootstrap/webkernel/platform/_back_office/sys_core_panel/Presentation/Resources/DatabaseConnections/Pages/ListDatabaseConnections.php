<?php
declare(strict_types=1);

namespace Webkernel\BackOffice\System\Presentation\Resources\DatabaseConnections\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkernel\BackOffice\System\Presentation\Resources\DatabaseConnections\DatabaseConnectionsResource;
use Webkernel\Panel\DatabaseConnectionsDataSource;

class ListDatabaseConnections extends ListRecords
{
    protected static string $resource = DatabaseConnectionsResource::class;

    /**
     * Populate the Sushi in-memory store before Filament renders the table.
     * Merges FSEngine-managed connections with static Laravel config entries.
     */
    public function mount(): void
    {
        parent::mount();
        DatabaseConnectionsDataSource::refreshRows();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
