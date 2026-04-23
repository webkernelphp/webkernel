<?php
declare(strict_types=1);

namespace Webkernel\BackOffice\System\Presentation\Resources\PanelArrays\Pages;

use Filament\Resources\Pages\CreateRecord;
use Webkernel\BackOffice\System\Presentation\Resources\PanelArrays\PanelArraysResource;
use Webkernel\Panel\DTO\PanelDTO;
use Webkernel\Panel\PanelArraysDataSource;
use Webkernel\Panel\Support\PanelConfigRepository;

class CreatePanelArrays extends CreateRecord
{
    protected static string $resource = PanelArraysResource::class;

    /**
     * @param array<string, mixed> $data
     */
    protected function handleRecordCreation(array $data): PanelArraysDataSource
    {
        $dto = PanelDTO::fromArray($data);

        if ($dto->isDefault) {
            PanelConfigRepository::setDefault($dto->id);
        }

        PanelConfigRepository::save($dto);
        PanelConfigRepository::invalidateCache();

        PanelArraysDataSource::refreshRows();

        return PanelArraysDataSource::find($dto->id)
            ?? (new PanelArraysDataSource())->forceFill($dto->toArray());
    }
}
