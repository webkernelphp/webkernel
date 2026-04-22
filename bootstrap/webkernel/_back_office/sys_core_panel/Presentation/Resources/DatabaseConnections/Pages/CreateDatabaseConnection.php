<?php
declare(strict_types=1);

namespace Webkernel\BackOffice\System\Presentation\Resources\DatabaseConnections\Pages;

use Filament\Resources\Pages\CreateRecord;
use Webkernel\BackOffice\System\Presentation\Resources\DatabaseConnections\DatabaseConnectionsResource;
use Webkernel\Panel\DatabaseConnectionsDataSource;
use Webkernel\Panel\DTO\DatabaseConnectionDTO;
use Webkernel\Panel\Support\DatabaseConnectionRepository;

class CreateDatabaseConnection extends CreateRecord
{
    protected static string $resource = DatabaseConnectionsResource::class;

    /**
     * @param array<string, mixed> $data
     */
    protected function handleRecordCreation(array $data): DatabaseConnectionsDataSource
    {
        $dto = DatabaseConnectionDTO::fromArray(
            array_merge($data, ['source' => 'dynamic'])
        );

        DatabaseConnectionRepository::save($dto);
        DatabaseConnectionRepository::invalidateCache();

        DatabaseConnectionsDataSource::refreshRows();

        return DatabaseConnectionsDataSource::find($dto->name)
            ?? (new DatabaseConnectionsDataSource())->forceFill($dto->toArray());
    }
}
