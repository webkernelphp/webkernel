<?php

namespace Webkernel\Builders\DBStudio\Resources\DashboardResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkernel\Builders\DBStudio\Resources\DashboardResource;

class ListDashboards extends ListRecords
{
    protected static string $resource = DashboardResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
