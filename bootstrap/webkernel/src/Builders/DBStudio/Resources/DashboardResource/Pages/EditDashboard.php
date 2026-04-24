<?php

namespace Webkernel\Builders\DBStudio\Resources\DashboardResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Webkernel\Builders\DBStudio\Actions\CreatePanelAction;
use Webkernel\Builders\DBStudio\Enums\PanelPlacement;
use Webkernel\Builders\DBStudio\Models\StudioDashboard;
use Webkernel\Builders\DBStudio\Resources\DashboardResource;

class EditDashboard extends EditRecord
{
    protected static string $resource = DashboardResource::class;

    protected function getHeaderActions(): array
    {
        /** @var StudioDashboard $record */
        $record = $this->record;

        return [
            CreatePanelAction::make()
                ->dashboardId($record->id)
                ->placement(PanelPlacement::Dashboard),
            DeleteAction::make(),
        ];
    }
}
