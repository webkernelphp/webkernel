<?php
declare(strict_types=1);

namespace Webkernel\BackOffice\System\Presentation\Resources\PanelArrays\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Webkernel\BackOffice\System\Presentation\Resources\PanelArrays\PanelArraysResource;
use Webkernel\Panel\DTO\PanelDTO;
use Webkernel\Panel\PanelArraysDataSource;
use Webkernel\Panel\Support\PanelConfigRepository;

class EditPanelArrays extends EditRecord
{
    protected static string $resource = PanelArraysResource::class;

    /**
     * Ensure rows are populated even when this page is accessed directly by URL
     * without visiting the list page first.
     */
    public function mount(int|string $record): void
    {
        PanelArraysDataSource::refreshRows();
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (PanelArraysDataSource $record): bool => $record->source === 'dynamic')
                ->using(function (PanelArraysDataSource $record): void {
                    PanelConfigRepository::remove($record->id);
                    PanelConfigRepository::invalidateCache();
                    PanelArraysDataSource::refreshRows();
                }),
        ];
    }

    /**
     * For static panels: merge introspected base + form changes into FSEngine
     * (they become 'dynamic' from this point forward).
     * For dynamic panels: merge with existing DTO and re-persist.
     *
     * @param array<string, mixed> $data
     */
    protected function handleRecordUpdate(Model $record, array $data): PanelArraysDataSource
    {
        /** @var PanelArraysDataSource $record */
        $base   = PanelConfigRepository::find($record->id)?->toArray() ?? [];
        $merged = array_merge($base, $data, ['id' => $record->id]);
        $dto    = PanelDTO::fromArray($merged);

        if ($dto->isDefault) {
            PanelConfigRepository::setDefault($dto->id);
        }

        PanelConfigRepository::save($dto);
        PanelConfigRepository::invalidateCache();

        PanelArraysDataSource::refreshRows();

        return PanelArraysDataSource::find($record->id)
            ?? $record->forceFill($dto->toArray());
    }
}
