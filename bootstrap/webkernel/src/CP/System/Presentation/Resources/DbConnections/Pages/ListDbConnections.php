<?php declare(strict_types=1);

namespace Webkernel\CP\System\Presentation\Resources\DbConnections\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkernel\CP\System\Presentation\Resources\DbConnections\DbConnectionResource;

class ListDbConnections extends ListRecords
{
    protected static string $resource = DbConnectionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
