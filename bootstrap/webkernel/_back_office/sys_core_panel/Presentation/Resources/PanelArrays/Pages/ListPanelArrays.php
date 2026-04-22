<?php
declare(strict_types=1);

namespace Webkernel\BackOffice\System\Presentation\Resources\PanelArrays\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkernel\BackOffice\System\Presentation\Resources\PanelArrays\PanelArraysResource;
use Webkernel\Panel\PanelArraysDataSource;

class ListPanelArrays extends ListRecords
{
    protected static string $resource = PanelArraysResource::class;

    /**
     * Populate the Sushi in-memory store before Filament renders the table.
     * FSTemplate::refreshRows() calls loadInspectorRows() which merges live
     * Filament introspection with FSEngine overrides.
     */
    public function mount(): void
    {
        parent::mount();
        PanelArraysDataSource::refreshRows();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
