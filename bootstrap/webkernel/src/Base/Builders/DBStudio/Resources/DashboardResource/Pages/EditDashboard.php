<?php

namespace Webkernel\Base\Builders\DBStudio\Resources\DashboardResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Webkernel\Base\Builders\DBStudio\Actions\CreatePanelAction;
use Webkernel\Base\Builders\DBStudio\Enums\PanelPlacement;
use Webkernel\Base\Builders\DBStudio\Models\StudioDashboard;
use Webkernel\Base\Builders\DBStudio\Resources\DashboardResource;

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
